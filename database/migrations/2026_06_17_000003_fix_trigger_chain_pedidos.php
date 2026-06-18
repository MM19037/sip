<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Problema: INSERT movimientos → trg_movimiento_insert → UPDATE pedidos (liberar) →
    //           trg_pedido_estado → CALL sp_asignar_lotes_fifo → UPDATE pedidos (total_costo)
    //           → ERROR 1442: pedidos ya está bloqueado por el UPDATE anterior.
    //
    // Solución: sp_asignar_lotes_fifo se llama desde PHP después de crear el movimiento,
    //           nunca desde dentro de la cadena de triggers.

    public function up(): void
    {
        // 1. trg_movimiento_insert: versión completa y corregida.
        //    - Restaura: creación de lote, gestión de solicitudes generales.
        //    - Mantiene: cursor SIN JOIN a pedidos (fix error 1442 del cursor).
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
                DECLARE v_stock_act INT;
                DECLARE v_stock_min INT;
                DECLARE v_alerta_id BIGINT UNSIGNED;
                DECLARE v_cantidad  INT;
                DECLARE v_prioridad TINYINT;

                -- 1. Actualizar stock actual del producto
                UPDATE productos
                   SET stock_actual = stock_actual + NEW.cantidad
                 WHERE id = NEW.producto_id;

                -- 2. Crear lote automáticamente para entradas
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

                -- 3. Evaluar stock mínimo y gestionar alertas + solicitudes generales
                SELECT stock_actual, stock_minimo
                  INTO v_stock_act, v_stock_min
                  FROM productos WHERE id = NEW.producto_id;

                IF v_stock_act < v_stock_min THEN
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
                        SET v_cantidad  = GREATEST(v_stock_min * 2 - v_stock_act, v_stock_min);
                        SET v_prioridad = IF(v_stock_act <= 0, 1, 2);

                        INSERT INTO solicitudes_reabastecimiento
                            (producto_id, pedido_id, alerta_id, cantidad_pedida,
                             estado, prioridad, created_at, updated_at)
                        VALUES
                            (NEW.producto_id, NULL, v_alerta_id, v_cantidad,
                             'pendiente', v_prioridad, NOW(), NOW());
                    END IF;
                ELSE
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

                -- 4. Si es entrada, liberar pedidos bloqueados por falta de stock.
                --    NOTA: El cursor itera solo detalle_pedido (sin JOIN a pedidos)
                --    para evitar el error 1442. El estado de cada pedido se consulta
                --    con SELECT INTO dentro del loop.
                --    La asignación FIFO (sp_asignar_lotes_fifo) NO se ejecuta aquí;
                --    lo llama PHP después de que toda la cadena de triggers termine.
                IF NEW.tipo = 'entrada' THEN
                    BEGIN
                        DECLARE cur_pedidos CURSOR FOR
                            SELECT DISTINCT pedido_id
                              FROM detalle_pedido
                             WHERE producto_id = NEW.producto_id;

                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

                        OPEN cur_pedidos;
                        loop_pedidos: LOOP
                            FETCH cur_pedidos INTO v_pedido_id;
                            IF done = 1 THEN LEAVE loop_pedidos; END IF;

                            SELECT estado INTO v_estado
                              FROM pedidos
                             WHERE id = v_pedido_id
                             LIMIT 1;

                            IF v_estado = 'esperando_stock' THEN
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

        // 2. trg_pedido_estado: se elimina el CALL sp_asignar_lotes_fifo.
        //    PHP lo llama directamente después de crear el movimiento, evitando
        //    que sp_asignar_lotes_fifo intente UPDATE pedidos dentro de una cadena
        //    de triggers que ya tiene pedidos bloqueado.
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

                -- Nota: la asignación FIFO (sp_asignar_lotes_fifo) cuando un pedido pasa
                -- de esperando_stock a pendiente ya no se hace aquí. PHP la ejecuta
                -- directamente después de registrar el movimiento de entrada.

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
                        UPDATE lotes l
                          JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
                          JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
                           SET l.cantidad_reservada = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
                         WHERE dp.pedido_id = NEW.id;

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
    }

    public function down(): void
    {
        // Restaurar trg_pedido_estado con el CALL sp_asignar_lotes_fifo
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
                IF NEW.estado = 'pendiente' AND OLD.estado = 'esperando_stock' THEN
                    CALL sp_asignar_lotes_fifo(NEW.id);
                END IF;
                IF NEW.estado = 'entregado' AND OLD.estado != 'entregado' THEN
                    UPDATE lotes l
                      JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
                      JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
                       SET l.cantidad_disponible = l.cantidad_disponible - dpl.cantidad_asignada,
                           l.cantidad_reservada  = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
                     WHERE dp.pedido_id = NEW.id;
                END IF;
                IF NEW.estado = 'cancelado' AND OLD.estado != 'cancelado' THEN
                    IF OLD.estado IN ('pendiente', 'en_produccion') THEN
                        UPDATE lotes l
                          JOIN detalle_pedido_lotes dpl ON dpl.lote_id = l.id
                          JOIN detalle_pedido dp ON dp.id = dpl.detalle_pedido_id
                           SET l.cantidad_reservada = GREATEST(l.cantidad_reservada - dpl.cantidad_asignada, 0)
                         WHERE dp.pedido_id = NEW.id;
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
    }
};
