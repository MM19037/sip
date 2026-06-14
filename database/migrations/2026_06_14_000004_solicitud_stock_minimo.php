<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hacer pedido_id nullable para solicitudes generales (sin pedido vinculado)
        Schema::table('solicitudes_reabastecimiento', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
        });
        Schema::table('solicitudes_reabastecimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_id')->nullable()->change();
            $table->foreign('pedido_id')->references('id')->on('pedidos')->nullOnDelete();
        });

        // 2. Reemplazar trg_movimiento_insert: agrega solicitud general junto a la alerta
        //    y la resuelve automáticamente cuando el stock se repone.
        DB::unprepared('DROP TRIGGER IF EXISTS trg_movimiento_insert');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_movimiento_insert
            AFTER INSERT ON movimientos_inventario
            FOR EACH ROW
            BEGIN
                DECLARE v_pedido_id  INT;
                DECLARE done         INT DEFAULT 0;
                DECLARE v_stock_act  INT;
                DECLARE v_stock_min  INT;
                DECLARE v_alerta_id  BIGINT UNSIGNED;
                DECLARE v_cantidad   INT;
                DECLARE v_prioridad  TINYINT;

                -- 1. Actualizar stock actual
                UPDATE productos
                   SET stock_actual = stock_actual + NEW.cantidad
                 WHERE id = NEW.producto_id;

                -- 2. Crear lote automáticamente para cada entrada de inventario
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

                -- 3. Evaluar stock mínimo y gestionar alerta + solicitud general
                SELECT stock_actual, stock_minimo
                  INTO v_stock_act, v_stock_min
                  FROM productos WHERE id = NEW.producto_id;

                IF v_stock_act < v_stock_min THEN
                    -- Stock bajo: crear alerta + solicitud si no existe una activa
                    IF NOT EXISTS (
                        SELECT 1 FROM alertas_stock
                         WHERE producto_id = NEW.producto_id
                           AND resuelta    = 0
                           AND pedido_id  IS NULL
                    ) THEN
                        INSERT INTO alertas_stock
                            (producto_id, stock_al_generar, stock_minimo, cantidad_faltante)
                        VALUES
                            (NEW.producto_id, v_stock_act, v_stock_min, 0);

                        SET v_alerta_id = LAST_INSERT_ID();
                        -- Sugerir reponer hasta 2× el mínimo; al menos el mínimo
                        SET v_cantidad  = GREATEST(v_stock_min * 2 - v_stock_act, v_stock_min);
                        -- Alta si no queda nada, Normal en los demás casos
                        SET v_prioridad = IF(v_stock_act <= 0, 1, 2);

                        INSERT INTO solicitudes_reabastecimiento
                            (producto_id, pedido_id, alerta_id, cantidad_pedida,
                             estado, prioridad, created_at, updated_at)
                        VALUES
                            (NEW.producto_id, NULL, v_alerta_id, v_cantidad,
                             'pendiente', v_prioridad, NOW(), NOW());
                    END IF;
                ELSE
                    -- Stock restaurado: resolver alertas y solicitudes generales abiertas
                    UPDATE alertas_stock
                       SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                     WHERE producto_id = NEW.producto_id
                       AND resuelta    = 0
                       AND pedido_id  IS NULL;

                    UPDATE solicitudes_reabastecimiento
                       SET estado = 'recibido'
                     WHERE producto_id = NEW.producto_id
                       AND pedido_id  IS NULL
                       AND estado IN ('pendiente', 'en_proceso');
                END IF;

                -- 4. Si es entrada, liberar pedidos bloqueados que usen este producto
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

        // 3. Actualizar vista para mostrar solicitudes sin pedido vinculado
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_solicitudes_reabastecimiento AS
            SELECT
                sr.id,
                sr.estado,
                sr.prioridad,
                p.nombre                                AS producto,
                c.nombre                                AS categoria,
                p.stock_actual,
                p.stock_reservado,
                (p.stock_actual - p.stock_reservado)    AS stock_disponible,
                p.stock_minimo,
                sr.cantidad_pedida,
                sr.pedido_id,
                pe.id                                   AS pedido_nro,
                cl.nombre                               AS cliente,
                sr.created_at                           AS solicitado_el,
                u.name                                  AS atendido_por
            FROM solicitudes_reabastecimiento sr
            JOIN productos  p  ON p.id  = sr.producto_id
            JOIN categorias c  ON c.id  = p.categoria_id
            LEFT JOIN pedidos    pe ON pe.id = sr.pedido_id
            LEFT JOIN clientes   cl ON cl.id = pe.cliente_id
            LEFT JOIN users      u  ON u.id  = sr.atendido_por
            ORDER BY sr.prioridad ASC, sr.created_at ASC
        SQL);
    }

    public function down(): void
    {
        // Restaurar trigger anterior (sin solicitud general)
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

                IF NEW.tipo = 'entrada' THEN
                    INSERT INTO lotes
                        (producto_id, movimiento_id, numero_lote, fecha_entrada,
                         cantidad_inicial, cantidad_disponible, costo_unitario)
                    VALUES (
                        NEW.producto_id, NEW.id,
                        CONCAT('L-', YEAR(NEW.fecha), '-', LPAD(NEW.id, 6, '0')),
                        NEW.fecha, NEW.cantidad, NEW.cantidad, NEW.costo_unitario
                    );
                END IF;

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

        // Restaurar vista anterior (JOIN en lugar de LEFT JOIN)
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_solicitudes_reabastecimiento AS
            SELECT
                sr.id, sr.estado, sr.prioridad,
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

        // Restaurar pedido_id NOT NULL
        Schema::table('solicitudes_reabastecimiento', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
        });
        Schema::table('solicitudes_reabastecimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_id')->nullable(false)->change();
            $table->foreign('pedido_id')->references('id')->on('pedidos');
        });
    }
};
