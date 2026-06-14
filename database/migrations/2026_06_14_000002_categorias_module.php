<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla maestra de categorías
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. Migrar categorías únicas existentes en productos
        $nombres = DB::table('productos')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        foreach ($nombres as $nombre) {
            DB::table('categorias')->insert([
                'nombre'     => $nombre,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Agregar FK nullable para poder poblarla antes de poner NOT NULL
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->after('nombre');
            $table->foreign('categoria_id')->references('id')->on('categorias');
        });

        // 4. Asignar categoria_id por nombre
        DB::statement('
            UPDATE productos p
            JOIN categorias c ON c.nombre = p.categoria
            SET p.categoria_id = c.id
        ');

        // 5. Poner NOT NULL y eliminar columna texto
        DB::statement('ALTER TABLE productos MODIFY COLUMN categoria_id BIGINT UNSIGNED NOT NULL');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_categoria_index');
            $table->dropColumn('categoria');
        });

        // 6. Actualizar todas las vistas que referenciaban productos.categoria

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_alertas_inventario AS
            SELECT
                p.id,
                p.nombre,
                c.nombre                          AS categoria,
                p.stock_actual,
                p.stock_minimo,
                (p.stock_minimo - p.stock_actual) AS unidades_faltantes,
                a.created_at                      AS alerta_desde
            FROM productos p
            JOIN categorias c    ON c.id = p.categoria_id
            JOIN alertas_stock a ON a.producto_id = p.id AND a.resuelta = 0 AND a.pedido_id IS NULL
            WHERE p.activo = 1
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_solicitudes_reabastecimiento AS
            SELECT
                sr.id,
                sr.estado,
                sr.prioridad,
                p.nombre                             AS producto,
                c.nombre                             AS categoria,
                p.stock_actual,
                p.stock_reservado,
                (p.stock_actual - p.stock_reservado) AS stock_disponible,
                p.stock_minimo,
                sr.cantidad_pedida,
                pe.id                                AS pedido_id,
                cl.nombre                            AS cliente,
                sr.created_at                        AS solicitado_el,
                u.name                               AS atendido_por
            FROM solicitudes_reabastecimiento sr
            JOIN productos  p  ON p.id  = sr.producto_id
            JOIN categorias c  ON c.id  = p.categoria_id
            JOIN pedidos    pe ON pe.id = sr.pedido_id
            JOIN clientes   cl ON cl.id = pe.cliente_id
            LEFT JOIN users u  ON u.id  = sr.atendido_por
            ORDER BY sr.prioridad ASC, sr.created_at ASC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_lotes_activos AS
            SELECT
                l.id,
                l.numero_lote,
                p.id                                           AS producto_id,
                p.nombre                                       AS producto,
                c.nombre                                       AS categoria,
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
            JOIN productos  p ON p.id = l.producto_id
            JOIN categorias c ON c.id = p.categoria_id
            ORDER BY l.producto_id, l.fecha_entrada ASC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_valoracion_inventario AS
            SELECT
                p.id                                                                AS producto_id,
                p.nombre                                                            AS producto,
                c.nombre                                                            AS categoria,
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
            JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN lotes l ON l.producto_id = p.id AND l.activo = 1 AND l.cantidad_disponible > 0
            WHERE p.activo = 1
            GROUP BY p.id, p.nombre, c.nombre, p.stock_actual, p.stock_reservado, p.costo_base
            ORDER BY c.nombre, p.nombre
        SQL);

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
            JOIN categorias c  ON c.id = pr.categoria_id
            JOIN detalle_pedido dp ON dp.producto_id = pr.id
            JOIN pedidos pe        ON pe.id = dp.pedido_id AND pe.estado = 'entregado'
            GROUP BY pr.id, pr.nombre, c.nombre,
                     YEAR(pe.fecha_entrega), MONTH(pe.fecha_entrega)
            ORDER BY c.nombre, pr.nombre, anio DESC, mes DESC
        SQL);
    }

    public function down(): void
    {
        // Restaurar vistas originales (con p.categoria texto)
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_rentabilidad_productos AS
            SELECT pr.id AS producto_id, pr.nombre AS producto, pr.categoria,
                   YEAR(pe.fecha_entrega) AS anio, MONTH(pe.fecha_entrega) AS mes,
                   COUNT(DISTINCT pe.id) AS pedidos_con_producto,
                   SUM(dp.cantidad) AS unidades_vendidas,
                   ROUND(AVG(dp.precio_unitario),2) AS precio_promedio_venta,
                   ROUND(AVG(dp.costo_unitario),2) AS costo_promedio_fifo,
                   SUM(dp.cantidad * dp.precio_unitario) AS ingresos,
                   SUM(dp.cantidad * dp.costo_unitario) AS costos,
                   SUM(dp.cantidad*(dp.precio_unitario-dp.costo_unitario)) AS ganancia_bruta,
                   ROUND(SUM(dp.cantidad*(dp.precio_unitario-dp.costo_unitario))/NULLIF(SUM(dp.cantidad*dp.precio_unitario),0)*100,2) AS margen_pct
            FROM productos pr
            JOIN detalle_pedido dp ON dp.producto_id = pr.id
            JOIN pedidos pe ON pe.id = dp.pedido_id AND pe.estado = 'entregado'
            GROUP BY pr.id, pr.nombre, pr.categoria, YEAR(pe.fecha_entrega), MONTH(pe.fecha_entrega)
            ORDER BY pr.categoria, pr.nombre, anio DESC, mes DESC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_valoracion_inventario AS
            SELECT p.id AS producto_id, p.nombre AS producto, p.categoria,
                   p.stock_actual, p.stock_reservado, (p.stock_actual-p.stock_reservado) AS stock_libre,
                   COALESCE(SUM(l.cantidad_disponible),0) AS unidades_en_lotes,
                   COALESCE(SUM(l.cantidad_disponible*l.costo_unitario),0) AS valor_total_fifo,
                   COALESCE(SUM(l.cantidad_disponible*l.costo_unitario)/NULLIF(SUM(l.cantidad_disponible),0),p.costo_base) AS costo_promedio_fifo,
                   COUNT(l.id) AS lotes_activos
            FROM productos p
            LEFT JOIN lotes l ON l.producto_id=p.id AND l.activo=1 AND l.cantidad_disponible>0
            WHERE p.activo=1
            GROUP BY p.id, p.nombre, p.categoria, p.stock_actual, p.stock_reservado, p.costo_base
            ORDER BY p.categoria, p.nombre
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_lotes_activos AS
            SELECT l.id, l.numero_lote, p.id AS producto_id, p.nombre AS producto, p.categoria,
                   l.fecha_entrada, l.cantidad_inicial, l.cantidad_disponible, l.cantidad_reservada,
                   (l.cantidad_disponible-l.cantidad_reservada) AS cantidad_libre,
                   l.costo_unitario, (l.cantidad_disponible*l.costo_unitario) AS valor_disponible,
                   l.activo, l.movimiento_id
            FROM lotes l JOIN productos p ON p.id=l.producto_id
            ORDER BY l.producto_id, l.fecha_entrada ASC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_solicitudes_reabastecimiento AS
            SELECT sr.id, sr.estado, sr.prioridad, p.nombre AS producto, p.categoria,
                   p.stock_actual, p.stock_reservado, (p.stock_actual-p.stock_reservado) AS stock_disponible,
                   p.stock_minimo, sr.cantidad_pedida, pe.id AS pedido_id, c.nombre AS cliente,
                   sr.created_at AS solicitado_el, u.name AS atendido_por
            FROM solicitudes_reabastecimiento sr
            JOIN productos p ON p.id=sr.producto_id
            JOIN pedidos pe ON pe.id=sr.pedido_id
            JOIN clientes c ON c.id=pe.cliente_id
            LEFT JOIN users u ON u.id=sr.atendido_por
            ORDER BY sr.prioridad ASC, sr.created_at ASC
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_alertas_inventario AS
            SELECT p.id, p.nombre, p.categoria, p.stock_actual, p.stock_minimo,
                   (p.stock_minimo-p.stock_actual) AS unidades_faltantes, a.created_at AS alerta_desde
            FROM productos p
            JOIN alertas_stock a ON a.producto_id=p.id AND a.resuelta=0 AND a.pedido_id IS NULL
            WHERE p.activo=1
        SQL);

        // Restaurar columna texto
        Schema::table('productos', function (Blueprint $table) {
            $table->string('categoria', 80)->nullable()->after('nombre');
        });

        DB::statement('
            UPDATE productos p
            JOIN categorias c ON c.id = p.categoria_id
            SET p.categoria = c.nombre
        ');

        DB::statement('ALTER TABLE productos MODIFY COLUMN categoria VARCHAR(80) NOT NULL');

        Schema::table('productos', function (Blueprint $table) {
            $table->index('categoria');
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });

        Schema::dropIfExists('categorias');
    }
};
