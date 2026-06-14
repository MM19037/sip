<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla lotes: un lote = una entrada de inventario
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('movimiento_id')
                ->comment('Movimiento de entrada que generó el lote');
            $table->foreign('movimiento_id')->references('id')->on('movimientos_inventario');
            $table->string('numero_lote', 30)->unique()->comment('Formato: L-YYYY-NNNNNN');
            $table->timestamp('fecha_entrada')->useCurrent();
            $table->integer('cantidad_inicial')->comment('Unidades al crear el lote');
            $table->integer('cantidad_disponible')->comment('Inicial menos consumido; decrece al entregar');
            $table->integer('cantidad_reservada')->default(0)
                ->comment('Subset de disponible comprometido a pedidos pendientes/en producción');
            $table->decimal('costo_unitario', 10, 2);
            $table->boolean('activo')->default(true);
            $table->index(['producto_id', 'fecha_entrada'], 'idx_lotes_prod_fecha');
        });

        // 2. Tabla detalle_pedido_lotes: asignación FIFO de lotes por línea de pedido
        Schema::create('detalle_pedido_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('detalle_pedido_id');
            $table->foreign('detalle_pedido_id')->references('id')->on('detalle_pedido')->onDelete('cascade');
            $table->foreignId('lote_id')->constrained('lotes');
            $table->integer('cantidad_asignada');
            $table->decimal('costo_unitario', 10, 2)->comment('Snapshot del costo del lote al asignar');
            $table->index('detalle_pedido_id', 'idx_dpl_detalle');
            $table->index('lote_id', 'idx_dpl_lote');
        });

        // 3. Stored procedure: asignación FIFO de lotes a todas las líneas de un pedido
        //    - Itera las líneas del pedido
        //    - Para cada línea encuentra lotes más antiguos con stock libre
        //    - Crea registros en detalle_pedido_lotes
        //    - Actualiza costo_unitario en detalle_pedido con el costo promedio ponderado FIFO
        //    - Recalcula total_costo del pedido
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_asignar_lotes_fifo(IN p_pedido_id INT)
            BEGIN
                DECLARE done       INT DEFAULT 0;
                DECLARE v_det_id   INT;
                DECLARE v_prod_id  INT;
                DECLARE v_cantidad INT;

                DECLARE cur_detalles CURSOR FOR
                    SELECT id, producto_id, cantidad
                    FROM detalle_pedido
                    WHERE pedido_id = p_pedido_id;

                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

                OPEN cur_detalles;
                det_loop: LOOP
                    FETCH cur_detalles INTO v_det_id, v_prod_id, v_cantidad;
                    IF done = 1 THEN LEAVE det_loop; END IF;

                    BEGIN
                        DECLARE v_restante    INT;
                        DECLARE v_costo_total DECIMAL(10,2);
                        DECLARE v_asignado    INT;
                        DECLARE v_lote_id     INT;
                        DECLARE v_libre       INT;
                        DECLARE v_lote_costo  DECIMAL(10,2);
                        DECLARE v_consumir    INT;

                        SET v_restante    = v_cantidad;
                        SET v_costo_total = 0;
                        SET v_asignado    = 0;

                        -- Idempotente: eliminar asignaciones previas
                        DELETE FROM detalle_pedido_lotes WHERE detalle_pedido_id = v_det_id;

                        lote_loop: LOOP
                            IF v_restante <= 0 THEN LEAVE lote_loop; END IF;

                            SET v_lote_id = NULL;

                            -- Lote FIFO más antiguo con unidades libres
                            SELECT id,
                                   (cantidad_disponible - cantidad_reservada),
                                   costo_unitario
                            INTO v_lote_id, v_libre, v_lote_costo
                            FROM lotes
                            WHERE producto_id = v_prod_id
                              AND (cantidad_disponible - cantidad_reservada) > 0
                              AND activo = 1
                            ORDER BY fecha_entrada ASC
                            LIMIT 1;

                            IF v_lote_id IS NULL THEN LEAVE lote_loop; END IF;

                            SET v_consumir = LEAST(v_libre, v_restante);

                            INSERT INTO detalle_pedido_lotes
                                (detalle_pedido_id, lote_id, cantidad_asignada, costo_unitario)
                            VALUES
                                (v_det_id, v_lote_id, v_consumir, v_lote_costo);

                            UPDATE lotes
                               SET cantidad_reservada = cantidad_reservada + v_consumir
                             WHERE id = v_lote_id;

                            SET v_costo_total = v_costo_total + (v_consumir * v_lote_costo);
                            SET v_asignado    = v_asignado + v_consumir;
                            SET v_restante    = v_restante - v_consumir;
                        END LOOP;

                        -- Actualizar costo FIFO ponderado en la línea del pedido
                        IF v_asignado > 0 THEN
                            UPDATE detalle_pedido
                               SET costo_unitario = ROUND(v_costo_total / v_asignado, 2)
                             WHERE id = v_det_id;
                        END IF;
                    END;
                END LOOP;
                CLOSE cur_detalles;

                -- Recalcular total_costo del pedido (ganancia se auto-actualiza al ser GENERATED)
                UPDATE pedidos
                   SET total_costo = (
                       SELECT COALESCE(SUM(cantidad * costo_unitario), 0)
                         FROM detalle_pedido
                        WHERE pedido_id = p_pedido_id
                   )
                 WHERE id = p_pedido_id;
            END
        SQL);

        // 4. Reemplazar trg_movimiento_insert: agrega creación de lote en entradas
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

                -- Crear lote automáticamente para cada entrada de inventario
                IF NEW.tipo = 'entrada' THEN
                    INSERT INTO lotes
                        (producto_id, movimiento_id, numero_lote, fecha_entrada,
                         cantidad_inicial, cantidad_disponible, costo_unitario)
                    VALUES (
                        NEW.producto_id,
                        NEW.id,
                        CONCAT('L-', YEAR(NEW.fecha), '-', LPAD(NEW.id, 6, '0')),
                        NEW.fecha,
                        NEW.cantidad,
                        NEW.cantidad,
                        NEW.costo_unitario
                    );
                END IF;

                -- Generar alerta general si stock cae bajo mínimo
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

                            SELECT COUNT(*) INTO @sin_stock
                              FROM detalle_pedido dp
                              JOIN productos pr ON pr.id = dp.producto_id
                             WHERE dp.pedido_id = v_pedido_id
                               AND (pr.stock_actual - pr.stock_reservado) < dp.cantidad;

                            IF @sin_stock = 0 THEN
                                -- Liberar pedido (dispara trg_pedido_estado que asigna lotes FIFO)
                                UPDATE pedidos
                                   SET estado = 'pendiente'
                                 WHERE id = v_pedido_id;

                                UPDATE productos pr
                                  JOIN detalle_pedido dp ON dp.producto_id = pr.id
                                   SET pr.stock_reservado = pr.stock_reservado + dp.cantidad
                                 WHERE dp.pedido_id = v_pedido_id;

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

        // 5. Reemplazar trg_pedido_estado: agrega asignación FIFO al liberar,
        //    consumo de lotes al entregar, y liberación al cancelar
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

                -- Asignar lotes FIFO cuando un pedido bloqueado es liberado
                IF NEW.estado = 'pendiente' AND OLD.estado = 'esperando_stock' THEN
                    CALL sp_asignar_lotes_fifo(NEW.id);
                END IF;

                -- Consumir lotes al marcar el pedido como entregado
                IF NEW.estado = 'entregado' AND OLD.estado != 'entregado' THEN
                    UPDATE lotes l
                      JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
                      JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
                       SET l.cantidad_disponible = l.cantidad_disponible - dpl.cantidad_asignada,
                           l.cantidad_reservada  = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
                     WHERE dp.pedido_id = NEW.id;
                END IF;

                -- Al cancelar: liberar reservas de lotes + stock_reservado + alertas/solicitudes
                IF NEW.estado = 'cancelado' AND OLD.estado != 'cancelado' THEN
                    IF OLD.estado IN ('pendiente', 'en_produccion') THEN
                        -- Devolver unidades reservadas a los lotes
                        UPDATE lotes l
                          JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
                          JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
                           SET l.cantidad_reservada = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
                         WHERE dp.pedido_id = NEW.id;

                        -- Devolver stock_reservado al producto
                        UPDATE productos pr
                          JOIN detalle_pedido dp ON dp.producto_id = pr.id
                           SET pr.stock_reservado = GREATEST(pr.stock_reservado - dp.cantidad, 0)
                         WHERE dp.pedido_id = NEW.id;
                    END IF;

                    UPDATE alertas_stock
                       SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                     WHERE pedido_id = NEW.id AND resuelta = 0;

                    UPDATE solicitudes_reabastecimiento
                       SET estado = 'cancelado'
                     WHERE pedido_id = NEW.id AND estado = 'pendiente';
                END IF;
            END
        SQL);

        // 6. Vistas del módulo Costos y Precios
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_lotes_activos AS
            SELECT
                l.id,
                l.numero_lote,
                p.id            AS producto_id,
                p.nombre        AS producto,
                p.categoria,
                l.fecha_entrada,
                l.cantidad_inicial,
                l.cantidad_disponible,
                l.cantidad_reservada,
                (l.cantidad_disponible - l.cantidad_reservada) AS cantidad_libre,
                l.costo_unitario,
                (l.cantidad_disponible * l.costo_unitario)     AS valor_disponible,
                l.activo,
                l.movimiento_id
            FROM lotes l
            JOIN productos p ON p.id = l.producto_id
            ORDER BY l.producto_id, l.fecha_entrada ASC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_valoracion_inventario AS
            SELECT
                p.id                                                                AS producto_id,
                p.nombre                                                            AS producto,
                p.categoria,
                p.stock_actual,
                p.stock_reservado,
                (p.stock_actual - p.stock_reservado)                               AS stock_libre,
                COALESCE(SUM(l.cantidad_disponible), 0)                            AS unidades_en_lotes,
                COALESCE(SUM(l.cantidad_disponible * l.costo_unitario), 0)         AS valor_total_fifo,
                COALESCE(
                    SUM(l.cantidad_disponible * l.costo_unitario) /
                    NULLIF(SUM(l.cantidad_disponible), 0),
                    p.costo_base
                )                                                                   AS costo_promedio_fifo,
                COUNT(l.id)                                                         AS lotes_activos
            FROM productos p
            LEFT JOIN lotes l ON l.producto_id = p.id
                              AND l.activo = 1
                              AND l.cantidad_disponible > 0
            WHERE p.activo = 1
            GROUP BY p.id, p.nombre, p.categoria, p.stock_actual, p.stock_reservado, p.costo_base
            ORDER BY p.categoria, p.nombre
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_rentabilidad_productos AS
            SELECT
                pr.id                                                              AS producto_id,
                pr.nombre                                                          AS producto,
                pr.categoria,
                YEAR(pe.fecha_entrega)                                             AS anio,
                MONTH(pe.fecha_entrega)                                            AS mes,
                COUNT(DISTINCT pe.id)                                              AS pedidos_con_producto,
                SUM(dp.cantidad)                                                   AS unidades_vendidas,
                ROUND(AVG(dp.precio_unitario), 2)                                  AS precio_promedio_venta,
                ROUND(AVG(dp.costo_unitario), 2)                                   AS costo_promedio_fifo,
                SUM(dp.cantidad * dp.precio_unitario)                              AS ingresos,
                SUM(dp.cantidad * dp.costo_unitario)                               AS costos,
                SUM(dp.cantidad * (dp.precio_unitario - dp.costo_unitario))        AS ganancia_bruta,
                ROUND(
                    SUM(dp.cantidad * (dp.precio_unitario - dp.costo_unitario)) /
                    NULLIF(SUM(dp.cantidad * dp.precio_unitario), 0) * 100
                , 2)                                                               AS margen_pct
            FROM productos pr
            JOIN detalle_pedido dp ON dp.producto_id = pr.id
            JOIN pedidos pe        ON pe.id = dp.pedido_id AND pe.estado = 'entregado'
            GROUP BY pr.id, pr.nombre, pr.categoria,
                     YEAR(pe.fecha_entrega), MONTH(pe.fecha_entrega)
            ORDER BY pr.categoria, pr.nombre, anio DESC, mes DESC
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_rentabilidad_productos');
        DB::statement('DROP VIEW IF EXISTS v_valoracion_inventario');
        DB::statement('DROP VIEW IF EXISTS v_lotes_activos');

        // Restaurar triggers de la migración anterior (stock_validation_flow)
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pedido_estado');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_pedido_estado
            AFTER UPDATE ON pedidos
            FOR EACH ROW
            BEGIN
                IF NEW.estado = 'en_produccion' AND OLD.estado != 'en_produccion' THEN
                    INSERT IGNORE INTO ordenes_produccion (pedido_id, estado)
                    VALUES (NEW.id, 'asignado');
                END IF;

                IF NEW.estado = 'cancelado' AND OLD.estado != 'cancelado' THEN
                    IF OLD.estado IN ('pendiente', 'en_produccion') THEN
                        UPDATE productos pr
                          JOIN detalle_pedido dp ON dp.producto_id = pr.id
                           SET pr.stock_reservado = GREATEST(pr.stock_reservado - dp.cantidad, 0)
                         WHERE dp.pedido_id = NEW.id;
                    END IF;

                    UPDATE alertas_stock
                       SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                     WHERE pedido_id = NEW.id AND resuelta = 0;

                    UPDATE solicitudes_reabastecimiento
                       SET estado = 'cancelado'
                     WHERE pedido_id = NEW.id AND estado = 'pendiente';
                END IF;
            END
        SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_movimiento_insert');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_movimiento_insert
            AFTER INSERT ON movimientos_inventario
            FOR EACH ROW
            BEGIN
                DECLARE v_pedido_id INT;
                DECLARE done        INT DEFAULT 0;

                UPDATE productos
                   SET stock_actual = stock_actual + NEW.cantidad
                 WHERE id = NEW.producto_id;

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

                            SELECT COUNT(*) INTO @sin_stock
                              FROM detalle_pedido dp
                              JOIN productos pr ON pr.id = dp.producto_id
                             WHERE dp.pedido_id = v_pedido_id
                               AND (pr.stock_actual - pr.stock_reservado) < dp.cantidad;

                            IF @sin_stock = 0 THEN
                                UPDATE pedidos SET estado = 'pendiente' WHERE id = v_pedido_id;
                                UPDATE productos pr
                                  JOIN detalle_pedido dp ON dp.producto_id = pr.id
                                   SET pr.stock_reservado = pr.stock_reservado + dp.cantidad
                                 WHERE dp.pedido_id = v_pedido_id;
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

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_asignar_lotes_fifo');
        Schema::dropIfExists('detalle_pedido_lotes');
        Schema::dropIfExists('lotes');
    }
};
