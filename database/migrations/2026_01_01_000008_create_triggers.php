<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trigger 1: Al insertar detalle → recalcular totales del pedido
        // CORRECCIÓN del SQL original: se usa variable local para evitar
        // ambigüedad al calcular total = subtotal - descuento en el mismo UPDATE.
        DB::unprepared('
            CREATE TRIGGER trg_detalle_insert
            AFTER INSERT ON detalle_pedido
            FOR EACH ROW
            BEGIN
                DECLARE v_subtotal    DECIMAL(10,2);
                DECLARE v_total_costo DECIMAL(10,2);
                DECLARE v_descuento   DECIMAL(10,2);

                SELECT COALESCE(SUM(cantidad * precio_unitario), 0)
                  INTO v_subtotal
                  FROM detalle_pedido WHERE pedido_id = NEW.pedido_id;

                SELECT COALESCE(SUM(cantidad * costo_unitario), 0)
                  INTO v_total_costo
                  FROM detalle_pedido WHERE pedido_id = NEW.pedido_id;

                SELECT descuento INTO v_descuento
                  FROM pedidos WHERE id = NEW.pedido_id;

                UPDATE pedidos
                   SET subtotal    = v_subtotal,
                       total_costo = v_total_costo,
                       total       = v_subtotal - v_descuento
                 WHERE id = NEW.pedido_id;
            END
        ');

        // Trigger 2: Al eliminar detalle → recalcular totales del pedido
        DB::unprepared('
            CREATE TRIGGER trg_detalle_delete
            AFTER DELETE ON detalle_pedido
            FOR EACH ROW
            BEGIN
                DECLARE v_subtotal    DECIMAL(10,2);
                DECLARE v_total_costo DECIMAL(10,2);
                DECLARE v_descuento   DECIMAL(10,2);

                SELECT COALESCE(SUM(cantidad * precio_unitario), 0)
                  INTO v_subtotal
                  FROM detalle_pedido WHERE pedido_id = OLD.pedido_id;

                SELECT COALESCE(SUM(cantidad * costo_unitario), 0)
                  INTO v_total_costo
                  FROM detalle_pedido WHERE pedido_id = OLD.pedido_id;

                SELECT descuento INTO v_descuento
                  FROM pedidos WHERE id = OLD.pedido_id;

                UPDATE pedidos
                   SET subtotal    = v_subtotal,
                       total_costo = v_total_costo,
                       total       = v_subtotal - v_descuento
                 WHERE id = OLD.pedido_id;
            END
        ');

        // Trigger 3: Al insertar movimiento → actualizar stock y generar alerta si aplica
        DB::unprepared('
            CREATE TRIGGER trg_movimiento_insert
            AFTER INSERT ON movimientos_inventario
            FOR EACH ROW
            BEGIN
                UPDATE productos
                   SET stock_actual = stock_actual + NEW.cantidad
                 WHERE id = NEW.producto_id;

                IF (SELECT stock_actual FROM productos WHERE id = NEW.producto_id) <
                   (SELECT stock_minimo  FROM productos WHERE id = NEW.producto_id) THEN
                    INSERT INTO alertas_stock (producto_id, stock_al_generar, stock_minimo)
                    SELECT id, stock_actual, stock_minimo
                      FROM productos
                     WHERE id = NEW.producto_id
                       AND NOT EXISTS (
                           SELECT 1 FROM alertas_stock
                            WHERE producto_id = NEW.producto_id AND resuelta = 0
                       );
                END IF;
            END
        ');

        // Trigger 4: Al cambiar estado del pedido → crear orden de producción
        // CORRECCIÓN del SQL original: se eliminó la actualización de fecha_entrega
        // dentro del trigger porque haría un UPDATE recursivo sobre la misma tabla.
        // La fecha_entrega se gestiona desde la capa de aplicación (PedidoService).
        DB::unprepared('
            CREATE TRIGGER trg_pedido_estado
            AFTER UPDATE ON pedidos
            FOR EACH ROW
            BEGIN
                IF NEW.estado = \'en_produccion\' AND OLD.estado != \'en_produccion\' THEN
                    INSERT IGNORE INTO ordenes_produccion (pedido_id, estado)
                    VALUES (NEW.id, \'asignado\');
                END IF;
            END
        ');

        // Trigger 5: Al completar orden de producción → marcar pedido como listo
        DB::unprepared('
            CREATE TRIGGER trg_op_completada
            AFTER UPDATE ON ordenes_produccion
            FOR EACH ROW
            BEGIN
                IF NEW.estado = \'completado\' AND OLD.estado != \'completado\' THEN
                    UPDATE pedidos SET estado = \'listo\' WHERE id = NEW.pedido_id;
                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_movimiento_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pedido_estado');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_op_completada');
    }
};
