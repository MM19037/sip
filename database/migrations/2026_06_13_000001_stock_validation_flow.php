<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar stock_reservado a productos
        Schema::table('productos', function (Blueprint $table) {
            $table->integer('stock_reservado')->default(0)->after('stock_actual')
                ->comment('Unidades comprometidas en pedidos pendientes/en producción');
        });

        // 2. Ampliar ENUM de estado en pedidos
        DB::statement("
            ALTER TABLE pedidos
            MODIFY COLUMN estado
            ENUM('esperando_stock','pendiente','en_produccion','listo','entregado','cancelado')
            NOT NULL DEFAULT 'pendiente'
        ");

        // 3. Ampliar tabla alertas_stock
        Schema::table('alertas_stock', function (Blueprint $table) {
            $table->integer('cantidad_faltante')->default(0)->after('stock_minimo')
                ->comment('Unidades que faltan para cubrir el pedido');
            $table->unsignedBigInteger('pedido_id')->nullable()->after('cantidad_faltante')
                ->comment('Pedido que disparó la alerta por falta de stock');
            $table->foreign('pedido_id')->references('id')->on('pedidos')->nullOnDelete();
        });

        // 4. Crear tabla solicitudes_reabastecimiento
        Schema::create('solicitudes_reabastecimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('pedido_id')->constrained('pedidos');
            $table->foreignId('alerta_id')->constrained('alertas_stock');
            $table->integer('cantidad_pedida')->comment('Cantidad sugerida a reabastecer');
            $table->enum('estado', ['pendiente', 'en_proceso', 'recibido', 'cancelado'])->default('pendiente');
            $table->tinyInteger('prioridad')->default(2)->comment('1=Alta 2=Normal 3=Baja');
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('atendido_por')->nullable()
                ->comment('Usuario administrador que la procesó');
            $table->timestamps();

            $table->foreign('atendido_por')->references('id')->on('users')->nullOnDelete();
            $table->index('estado');
            $table->index('producto_id');
            $table->index('pedido_id');
            $table->index('prioridad');
        });

        // 5. Reemplazar trg_movimiento_insert
        //    Combina: actualizar stock + alerta general + liberar pedidos bloqueados al haber entrada
        DB::unprepared('DROP TRIGGER IF EXISTS trg_movimiento_insert');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_movimiento_insert
            AFTER INSERT ON movimientos_inventario
            FOR EACH ROW
            BEGIN
                DECLARE v_pedido_id INT;
                DECLARE done        INT DEFAULT 0;

                -- Actualizar stock actual
                UPDATE productos
                   SET stock_actual = stock_actual + NEW.cantidad
                 WHERE id = NEW.producto_id;

                -- Generar alerta general si stock cae bajo mínimo (solo alertas sin pedido asociado)
                IF (SELECT stock_actual FROM productos WHERE id = NEW.producto_id) <
                   (SELECT stock_minimo  FROM productos WHERE id = NEW.producto_id) THEN
                    INSERT INTO alertas_stock (producto_id, stock_al_generar, stock_minimo, cantidad_faltante)
                    SELECT id, stock_actual, stock_minimo, 0
                      FROM productos
                     WHERE id = NEW.producto_id
                       AND NOT EXISTS (
                           SELECT 1 FROM alertas_stock
                            WHERE producto_id = NEW.producto_id
                              AND resuelta    = 0
                              AND pedido_id  IS NULL
                       );
                END IF;

                -- Si es entrada, liberar pedidos bloqueados que usen este producto
                IF NEW.tipo = 'entrada' THEN
                    BEGIN
                        DECLARE cur_pedidos CURSOR FOR
                            SELECT DISTINCT dp.pedido_id
                              FROM detalle_pedido dp
                              JOIN pedidos p ON p.id = dp.pedido_id
                             WHERE dp.producto_id = NEW.producto_id
                               AND p.estado = 'esperando_stock';

                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

                        OPEN cur_pedidos;
                        loop_pedidos: LOOP
                            FETCH cur_pedidos INTO v_pedido_id;
                            IF done = 1 THEN LEAVE loop_pedidos; END IF;

                            -- Verificar que TODOS los productos del pedido tengan stock disponible
                            SELECT COUNT(*) INTO @sin_stock
                              FROM detalle_pedido dp
                              JOIN productos pr ON pr.id = dp.producto_id
                             WHERE dp.pedido_id = v_pedido_id
                               AND (pr.stock_actual - pr.stock_reservado) < dp.cantidad;

                            IF @sin_stock = 0 THEN
                                -- Liberar pedido
                                UPDATE pedidos
                                   SET estado = 'pendiente'
                                 WHERE id = v_pedido_id;

                                -- Reservar stock para todas las líneas del pedido
                                UPDATE productos pr
                                  JOIN detalle_pedido dp ON dp.producto_id = pr.id
                                   SET pr.stock_reservado = pr.stock_reservado + dp.cantidad
                                 WHERE dp.pedido_id = v_pedido_id;

                                -- Cerrar alertas y solicitudes del pedido
                                UPDATE alertas_stock
                                   SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                                 WHERE pedido_id = v_pedido_id AND resuelta = 0;

                                UPDATE solicitudes_reabastecimiento
                                   SET estado = 'recibido'
                                 WHERE pedido_id = v_pedido_id AND estado != 'cancelado';
                            END IF;

                        END LOOP;
                        CLOSE cur_pedidos;
                    END;
                END IF;
            END
        SQL);

        // 6. Reemplazar trg_pedido_estado
        //    Combina: crear OP al pasar a en_produccion + liberar stock al cancelar
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pedido_estado');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_pedido_estado
            AFTER UPDATE ON pedidos
            FOR EACH ROW
            BEGIN
                -- Crear orden de producción al pasar a en_produccion
                IF NEW.estado = 'en_produccion' AND OLD.estado != 'en_produccion' THEN
                    INSERT IGNORE INTO ordenes_produccion (pedido_id, estado)
                    VALUES (NEW.id, 'asignado');
                END IF;

                -- Al cancelar: liberar stock reservado y cerrar alertas/solicitudes
                IF NEW.estado = 'cancelado' AND OLD.estado != 'cancelado' THEN
                    -- Solo liberar stock si el pedido tenía stock reservado (pendiente o en producción)
                    IF OLD.estado IN ('pendiente', 'en_produccion') THEN
                        UPDATE productos pr
                          JOIN detalle_pedido dp ON dp.producto_id = pr.id
                           SET pr.stock_reservado = GREATEST(pr.stock_reservado - dp.cantidad, 0)
                         WHERE dp.pedido_id = NEW.id;
                    END IF;

                    -- Cerrar alertas de stock del pedido
                    UPDATE alertas_stock
                       SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                     WHERE pedido_id = NEW.id AND resuelta = 0;

                    -- Cancelar solicitudes de reabastecimiento pendientes
                    UPDATE solicitudes_reabastecimiento
                       SET estado = 'cancelado'
                     WHERE pedido_id = NEW.id AND estado = 'pendiente';
                END IF;
            END
        SQL);

        // 7. Vista de solicitudes de reabastecimiento
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_solicitudes_reabastecimiento AS
            SELECT
                sr.id,
                sr.estado,
                sr.prioridad,
                p.nombre        AS producto,
                p.categoria,
                p.stock_actual,
                p.stock_reservado,
                (p.stock_actual - p.stock_reservado) AS stock_disponible,
                p.stock_minimo,
                sr.cantidad_pedida,
                pe.id           AS pedido_id,
                c.nombre        AS cliente,
                sr.created_at   AS solicitado_el,
                u.name          AS atendido_por
            FROM solicitudes_reabastecimiento sr
            JOIN productos  p  ON p.id  = sr.producto_id
            JOIN pedidos    pe ON pe.id = sr.pedido_id
            JOIN clientes   c  ON c.id  = pe.cliente_id
            LEFT JOIN users u  ON u.id  = sr.atendido_por
            ORDER BY sr.prioridad ASC, sr.created_at ASC
        SQL);

        // 8. Actualizar v_dashboard para incluir esperando_stock
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_dashboard AS
            SELECT
                (SELECT COUNT(*) FROM pedidos WHERE estado NOT IN ('entregado','cancelado'))  AS pedidos_activos,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente')                     AS pedidos_pendientes,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'esperando_stock')               AS esperando_stock,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'en_produccion')                 AS en_produccion,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'listo')                         AS listos_para_entrega,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado'
                   AND DATE(fecha_entrega) = CURDATE())                                        AS entregados_hoy,
                (SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado = 'entregado'
                   AND MONTH(fecha_entrega) = MONTH(CURDATE())
                   AND YEAR(fecha_entrega)  = YEAR(CURDATE()))                                 AS ventas_mes,
                (SELECT COALESCE(SUM(ganancia),0) FROM pedidos WHERE estado = 'entregado'
                   AND MONTH(fecha_entrega) = MONTH(CURDATE())
                   AND YEAR(fecha_entrega)  = YEAR(CURDATE()))                                 AS ganancia_mes,
                (SELECT COUNT(*) FROM alertas_stock WHERE resuelta = 0)                        AS alertas_stock_activas,
                (SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo
                   AND activo = 1)                                                             AS productos_bajo_stock
        SQL);

        // 9. v_pedidos_activos: sin cambios estructurales pero se regenera
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_pedidos_activos AS
            SELECT
                p.id,
                p.estado,
                c.nombre        AS cliente,
                c.telefono      AS cliente_telefono,
                u.name          AS recepcionista,
                p.total,
                p.ganancia,
                p.fecha_pedido,
                p.fecha_prometida,
                p.notas,
                op.estado       AS estado_produccion,
                op.usuario_id   AS operario_id
            FROM pedidos p
            JOIN clientes  c  ON c.id = p.cliente_id
            JOIN users     u  ON u.id = p.usuario_id
            LEFT JOIN ordenes_produccion op ON op.pedido_id = p.id
            WHERE p.estado NOT IN ('entregado', 'cancelado')
            ORDER BY p.fecha_prometida ASC, p.id ASC
        SQL);

        // 10. v_alertas_inventario: distinguir alertas generales de las de pedido
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_alertas_inventario AS
            SELECT
                p.id,
                p.nombre,
                p.categoria,
                p.stock_actual,
                p.stock_minimo,
                (p.stock_minimo - p.stock_actual) AS unidades_faltantes,
                a.created_at                      AS alerta_desde
            FROM productos p
            JOIN alertas_stock a ON a.producto_id = p.id AND a.resuelta = 0 AND a.pedido_id IS NULL
            WHERE p.activo = 1
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_solicitudes_reabastecimiento');

        // Restaurar vistas originales
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_alertas_inventario AS
            SELECT p.id, p.nombre, p.categoria, p.stock_actual, p.stock_minimo,
                   (p.stock_minimo - p.stock_actual) AS unidades_faltantes, a.created_at AS alerta_desde
              FROM productos p
              JOIN alertas_stock a ON a.producto_id = p.id AND a.resuelta = 0
             WHERE p.activo = 1
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_pedidos_activos AS
            SELECT p.id, p.estado, c.nombre AS cliente, c.telefono AS cliente_telefono,
                   u.name AS recepcionista, p.total, p.ganancia, p.fecha_pedido,
                   p.fecha_prometida, p.notas, op.estado AS estado_produccion, op.usuario_id AS operario_id
              FROM pedidos p
              JOIN clientes c ON c.id = p.cliente_id
              JOIN users u ON u.id = p.usuario_id
              LEFT JOIN ordenes_produccion op ON op.pedido_id = p.id
             WHERE p.estado NOT IN ('entregado','cancelado')
             ORDER BY p.fecha_prometida ASC, p.id ASC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_dashboard AS
            SELECT
                (SELECT COUNT(*) FROM pedidos WHERE estado NOT IN ('entregado','cancelado')) AS pedidos_activos,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente')                    AS pedidos_pendientes,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'en_produccion')                AS en_produccion,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'listo')                        AS listos_para_entrega,
                (SELECT COUNT(*) FROM pedidos WHERE estado = 'entregado' AND DATE(fecha_entrega) = CURDATE()) AS entregados_hoy,
                (SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado = 'entregado' AND MONTH(fecha_entrega) = MONTH(CURDATE()) AND YEAR(fecha_entrega) = YEAR(CURDATE())) AS ventas_mes,
                (SELECT COALESCE(SUM(ganancia),0) FROM pedidos WHERE estado = 'entregado' AND MONTH(fecha_entrega) = MONTH(CURDATE()) AND YEAR(fecha_entrega) = YEAR(CURDATE())) AS ganancia_mes,
                (SELECT COUNT(*) FROM alertas_stock WHERE resuelta = 0) AS alertas_stock_activas,
                (SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo AND activo = 1) AS productos_bajo_stock
        SQL);

        // Restaurar triggers originales
        DB::unprepared('DROP TRIGGER IF EXISTS trg_movimiento_insert');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_movimiento_insert
            AFTER INSERT ON movimientos_inventario
            FOR EACH ROW
            BEGIN
                UPDATE productos SET stock_actual = stock_actual + NEW.cantidad WHERE id = NEW.producto_id;

                IF (SELECT stock_actual FROM productos WHERE id = NEW.producto_id) <
                   (SELECT stock_minimo  FROM productos WHERE id = NEW.producto_id) THEN
                    INSERT INTO alertas_stock (producto_id, stock_al_generar, stock_minimo)
                    SELECT id, stock_actual, stock_minimo
                      FROM productos
                     WHERE id = NEW.producto_id
                       AND NOT EXISTS (
                           SELECT 1 FROM alertas_stock WHERE producto_id = NEW.producto_id AND resuelta = 0
                       );
                END IF;
            END
        SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_pedido_estado');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_pedido_estado
            AFTER UPDATE ON pedidos
            FOR EACH ROW
            BEGIN
                IF NEW.estado = 'en_produccion' AND OLD.estado != 'en_produccion' THEN
                    INSERT IGNORE INTO ordenes_produccion (pedido_id, estado) VALUES (NEW.id, 'asignado');
                END IF;
            END
        SQL);

        Schema::dropIfExists('solicitudes_reabastecimiento');

        Schema::table('alertas_stock', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
            $table->dropColumn(['cantidad_faltante', 'pedido_id']);
        });

        // Mover pedidos en esperando_stock a pendiente antes de quitar el ENUM
        DB::table('pedidos')->where('estado', 'esperando_stock')->update(['estado' => 'pendiente']);

        DB::statement("
            ALTER TABLE pedidos
            MODIFY COLUMN estado
            ENUM('pendiente','en_produccion','listo','entregado','cancelado')
            NOT NULL DEFAULT 'pendiente'
        ");

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('stock_reservado');
        });
    }
};
