<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // El cursor original hacía JOIN a pedidos y luego UPDATE pedidos en el mismo bloque.
        // MariaDB lanza error 1442 porque considera la tabla 'pedidos' en uso mientras el
        // cursor la lee. Solución: el cursor solo itera detalle_pedido (sin JOIN a pedidos)
        // y se usa SELECT INTO para verificar el estado de cada pedido por separado.
        DB::unprepared('DROP TRIGGER IF EXISTS trg_movimiento_insert');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_movimiento_insert
            AFTER INSERT ON movimientos_inventario
            FOR EACH ROW
            BEGIN
                DECLARE v_pedido_id INT;
                DECLARE v_estado    VARCHAR(30);
                DECLARE v_sin_stock INT;
                DECLARE done        INT DEFAULT 0;

                -- 1. Actualizar stock actual del producto
                UPDATE productos
                   SET stock_actual = stock_actual + NEW.cantidad
                 WHERE id = NEW.producto_id;

                -- 2. Generar alerta general si stock cae bajo mínimo
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

                -- 3. Si es entrada, intentar liberar pedidos bloqueados por falta de stock
                IF NEW.tipo = 'entrada' THEN
                    BEGIN
                        -- IMPORTANTE: el cursor NO hace JOIN a pedidos para evitar el error 1442.
                        -- El estado de cada pedido se consulta por separado con SELECT INTO.
                        DECLARE cur_pedidos CURSOR FOR
                            SELECT DISTINCT pedido_id
                              FROM detalle_pedido
                             WHERE producto_id = NEW.producto_id;

                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

                        OPEN cur_pedidos;
                        loop_pedidos: LOOP
                            FETCH cur_pedidos INTO v_pedido_id;
                            IF done = 1 THEN LEAVE loop_pedidos; END IF;

                            -- Consultar estado del pedido sin cursor (SELECT INTO)
                            SELECT estado INTO v_estado
                              FROM pedidos
                             WHERE id = v_pedido_id
                             LIMIT 1;

                            IF v_estado = 'esperando_stock' THEN
                                -- Verificar que TODOS los productos del pedido ya tienen stock
                                SELECT COUNT(*) INTO v_sin_stock
                                  FROM detalle_pedido dp
                                  JOIN productos pr ON pr.id = dp.producto_id
                                 WHERE dp.pedido_id = v_pedido_id
                                   AND (pr.stock_actual - pr.stock_reservado) < dp.cantidad;

                                IF v_sin_stock = 0 THEN
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
                            END IF;

                        END LOOP;
                        CLOSE cur_pedidos;
                    END;
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
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
                                UPDATE productos pr JOIN detalle_pedido dp ON dp.producto_id = pr.id
                                   SET pr.stock_reservado = pr.stock_reservado + dp.cantidad
                                 WHERE dp.pedido_id = v_pedido_id;
                                UPDATE alertas_stock SET resuelta = 1, resuelta_at = CURRENT_TIMESTAMP
                                 WHERE pedido_id = v_pedido_id AND resuelta = 0;
                                UPDATE solicitudes_reabastecimiento SET estado = 'recibido'
                                 WHERE pedido_id = v_pedido_id AND estado != 'cancelado';
                            END IF;
                        END LOOP;
                        CLOSE cur_pedidos;
                    END;
                END IF;
            END
        SQL);
    }
};
