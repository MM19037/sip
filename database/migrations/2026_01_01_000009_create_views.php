<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista: pedidos activos con información completa
        DB::statement('
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
            WHERE p.estado NOT IN (\'entregado\', \'cancelado\')
            ORDER BY p.fecha_prometida ASC, p.id ASC
        ');

        // Vista: resumen del dashboard administrativo
        DB::statement('
            CREATE OR REPLACE VIEW v_dashboard AS
            SELECT
                (SELECT COUNT(*) FROM pedidos WHERE estado NOT IN (\'entregado\',\'cancelado\')) AS pedidos_activos,
                (SELECT COUNT(*) FROM pedidos WHERE estado = \'pendiente\')                    AS pedidos_pendientes,
                (SELECT COUNT(*) FROM pedidos WHERE estado = \'en_produccion\')                AS en_produccion,
                (SELECT COUNT(*) FROM pedidos WHERE estado = \'listo\')                        AS listos_para_entrega,
                (SELECT COUNT(*) FROM pedidos WHERE estado = \'entregado\'
                   AND DATE(fecha_entrega) = CURDATE())                                       AS entregados_hoy,
                (SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado = \'entregado\'
                   AND MONTH(fecha_entrega) = MONTH(CURDATE())
                   AND YEAR(fecha_entrega)  = YEAR(CURDATE()))                                AS ventas_mes,
                (SELECT COALESCE(SUM(ganancia),0) FROM pedidos WHERE estado = \'entregado\'
                   AND MONTH(fecha_entrega) = MONTH(CURDATE())
                   AND YEAR(fecha_entrega)  = YEAR(CURDATE()))                                AS ganancia_mes,
                (SELECT COUNT(*) FROM alertas_stock WHERE resuelta = 0)                       AS alertas_stock_activas,
                (SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo
                   AND activo = 1)                                                            AS productos_bajo_stock
        ');

        // Vista: historial de pedidos por cliente
        DB::statement('
            CREATE OR REPLACE VIEW v_historial_cliente AS
            SELECT
                c.id                              AS cliente_id,
                c.nombre                          AS cliente,
                c.telefono,
                c.email,
                COUNT(p.id)                       AS total_pedidos,
                COALESCE(SUM(p.total), 0)         AS total_gastado,
                MAX(p.fecha_pedido)               AS ultimo_pedido,
                COALESCE(AVG(p.total), 0)         AS ticket_promedio
            FROM clientes c
            LEFT JOIN pedidos p ON p.cliente_id = c.id
            GROUP BY c.id, c.nombre, c.telefono, c.email
        ');

        // Vista: productos con alerta de stock activa
        DB::statement('
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
            JOIN alertas_stock a ON a.producto_id = p.id AND a.resuelta = 0
            WHERE p.activo = 1
        ');

        // Vista: rendimiento de producción por operario
        DB::statement('
            CREATE OR REPLACE VIEW v_rendimiento_produccion AS
            SELECT
                u.id,
                u.name                                AS nombre,
                COUNT(op.id)                          AS ordenes_completadas,
                COALESCE(AVG(op.tiempo_minutos), 0)   AS tiempo_promedio_min,
                COALESCE(SUM(p.total), 0)             AS valor_producido
            FROM users u
            LEFT JOIN ordenes_produccion op ON op.usuario_id = u.id AND op.estado = \'completado\'
            LEFT JOIN pedidos p ON p.id = op.pedido_id
            WHERE u.rol = \'produccion\'
            GROUP BY u.id, u.name
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_rendimiento_produccion');
        DB::statement('DROP VIEW IF EXISTS v_alertas_inventario');
        DB::statement('DROP VIEW IF EXISTS v_historial_cliente');
        DB::statement('DROP VIEW IF EXISTS v_dashboard');
        DB::statement('DROP VIEW IF EXISTS v_pedidos_activos');
    }
};
