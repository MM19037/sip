<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // v_rentabilidad_productos calculaba ingresos y ganancia_bruta sobre el precio
    // bruto de línea (sin descontar los descuentos de pedido), lo que producía
    // una ganancia mayor a la real mostrada en el dashboard.
    //
    // Fix: multiplicar el precio de línea por (pe.total / pe.subtotal) para distribuir
    // el descuento del pedido proporcionalmente entre los productos que lo componen.

    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_rentabilidad_productos AS
            SELECT
                pr.id                                                                       AS producto_id,
                pr.nombre                                                                   AS producto,
                c.nombre                                                                    AS categoria,
                YEAR(pe.fecha_entrega)                                                      AS anio,
                MONTH(pe.fecha_entrega)                                                     AS mes,
                COUNT(DISTINCT pe.id)                                                       AS pedidos_con_producto,
                SUM(dp.cantidad)                                                            AS unidades_vendidas,
                ROUND(AVG(dp.precio_unitario), 2)                                           AS precio_promedio_venta,
                ROUND(AVG(dp.costo_unitario), 2)                                            AS costo_promedio_fifo,
                -- Ingresos reales: precio bruto ajustado por el factor de descuento del pedido
                -- Factor = pe.total / pe.subtotal = (subtotal - descuento) / subtotal
                ROUND(SUM(
                    dp.cantidad * dp.precio_unitario
                    * (pe.total / NULLIF(pe.subtotal, 0))
                ), 2)                                                                       AS ingresos,
                ROUND(SUM(dp.cantidad * dp.costo_unitario), 2)                             AS costos,
                ROUND(SUM(
                    dp.cantidad * dp.precio_unitario * (pe.total / NULLIF(pe.subtotal, 0))
                    - dp.cantidad * dp.costo_unitario
                ), 2)                                                                       AS ganancia_bruta,
                ROUND(
                    SUM(
                        dp.cantidad * dp.precio_unitario * (pe.total / NULLIF(pe.subtotal, 0))
                        - dp.cantidad * dp.costo_unitario
                    ) /
                    NULLIF(SUM(
                        dp.cantidad * dp.precio_unitario * (pe.total / NULLIF(pe.subtotal, 0))
                    ), 0) * 100
                , 2)                                                                        AS margen_pct
            FROM productos pr
            JOIN categorias c      ON c.id  = pr.categoria_id
            JOIN detalle_pedido dp ON dp.producto_id = pr.id
            JOIN pedidos pe        ON pe.id = dp.pedido_id AND pe.estado = 'entregado'
            GROUP BY pr.id, pr.nombre, c.nombre,
                     YEAR(pe.fecha_entrega), MONTH(pe.fecha_entrega)
            ORDER BY c.nombre, pr.nombre, anio DESC, mes DESC
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_rentabilidad_productos AS
            SELECT
                pr.id                                                              AS producto_id,
                pr.nombre                                                          AS producto,
                c.nombre                                                           AS categoria,
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
            JOIN categorias c      ON c.id  = pr.categoria_id
            JOIN detalle_pedido dp ON dp.producto_id = pr.id
            JOIN pedidos pe        ON pe.id = dp.pedido_id AND pe.estado = 'entregado'
            GROUP BY pr.id, pr.nombre, c.nombre,
                     YEAR(pe.fecha_entrega), MONTH(pe.fecha_entrega)
            ORDER BY c.nombre, pr.nombre, anio DESC, mes DESC
        SQL);
    }
};
