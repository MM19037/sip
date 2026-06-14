<?php

namespace Database\Seeders;

use App\Models\MovimientoInventario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limpia pedidos/productos/categorías y carga datos de prueba con lotes FIFO.
 * Uso: php artisan db:seed --class=TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->limpiar();
        $cats = $this->insertarCategorias();
        $this->insertarProductos($cats);
        $this->insertarMovimientosIniciales();
    }

    // ----------------------------------------------------------------
    // 1. Limpieza en orden de FK
    // ----------------------------------------------------------------
    private function limpiar(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'detalle_pedido_lotes', 'lotes',
            'solicitudes_reabastecimiento', 'alertas_stock',
            'detalle_pedido', 'ordenes_produccion',
            'movimientos_inventario', 'pedidos',
            'productos', 'categorias',
        ] as $tabla) {
            if (Schema::hasTable($tabla)) {
                DB::table($tabla)->truncate();
                DB::statement("ALTER TABLE {$tabla} AUTO_INCREMENT = 1");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info('  Tablas limpiadas.');
    }

    // ----------------------------------------------------------------
    // 2. Categorías con descripción
    // ----------------------------------------------------------------
    private function insertarCategorias(): array
    {
        $ahora = now();

        $filas = [
            ['nombre' => 'Tazas',      'descripcion' => 'Tazas cerámicas y de colores para sublimación e impresión personalizada.'],
            ['nombre' => 'Camisetas',  'descripcion' => 'Prendas de algodón y poliéster para transfer, sublimación y bordado.'],
            ['nombre' => 'Lapiceros',  'descripcion' => 'Bolígrafos metálicos y plásticos para grabado láser y serigrafía.'],
            ['nombre' => 'Viniles',    'descripcion' => 'Láminas de vinil adhesivo y transfer para corte en plotter y prensa.'],
            ['nombre' => 'Bolsas',     'descripcion' => 'Tote bags y bolsas de lienzo para serigrafía y sublimación.'],
            ['nombre' => 'Gorras',     'descripcion' => 'Gorras y sombreros para sublimación y bordado computarizado.'],
            ['nombre' => 'Accesorios', 'descripcion' => 'Mousepads, termos, llaveros y otros artículos promocionales sublimables.'],
        ];

        $ids = [];
        foreach ($filas as $f) {
            $id = DB::table('categorias')->insertGetId([
                'nombre'      => $f['nombre'],
                'descripcion' => $f['descripcion'],
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);
            $ids[$f['nombre']] = $id;
        }

        $this->command->info('  ' . count($ids) . ' categorías insertadas.');
        return $ids;
    }

    // ----------------------------------------------------------------
    // 3. Productos con todos los campos y categoria_id
    // ----------------------------------------------------------------
    private function insertarProductos(array $cats): void
    {
        $ahora = now();

        $productos = [
            // ── Tazas ────────────────────────────────────────────────
            ['nombre' => 'Taza cerámica blanca 11oz',   'cat' => 'Tazas',      'costo' => 5.00,  'margen' => 120.00, 'minimo' => 10,
             'desc'   => 'Taza de cerámica blanca estándar para sublimación. Capacidad 325 ml. Apta para microondas y lavavajillas.'],
            ['nombre' => 'Taza mágica negra 11oz',       'cat' => 'Tazas',      'costo' => 7.50,  'margen' => 100.00, 'minimo' => 8,
             'desc'   => 'Taza mágica recubierta negra. Al contacto con calor revela el diseño sublimado. Capacidad 325 ml.'],

            // ── Camisetas ────────────────────────────────────────────
            ['nombre' => 'Camiseta algodón blanca talla S', 'cat' => 'Camisetas', 'costo' => 6.50, 'margen' => 105.00, 'minimo' => 10,
             'desc'   => 'Camiseta 100% algodón peinado 180 g/m². Blanca, cuello redondo. Talla S.'],
            ['nombre' => 'Camiseta algodón blanca talla M', 'cat' => 'Camisetas', 'costo' => 6.50, 'margen' => 105.00, 'minimo' => 10,
             'desc'   => 'Camiseta 100% algodón peinado 180 g/m². Blanca, cuello redondo. Talla M.'],
            ['nombre' => 'Camiseta algodón blanca talla L', 'cat' => 'Camisetas', 'costo' => 7.00, 'margen' => 100.00, 'minimo' => 8,
             'desc'   => 'Camiseta 100% algodón peinado 180 g/m². Blanca, cuello redondo. Talla L.'],

            // ── Lapiceros ────────────────────────────────────────────
            ['nombre' => 'Lapicero metálico plateado',   'cat' => 'Lapiceros',  'costo' => 1.80,  'margen' => 140.00, 'minimo' => 25,
             'desc'   => 'Lapicero metálico acabado plateado. Mecanismo de giro. Superficie grabable con láser.'],
            ['nombre' => 'Lapicero metálico dorado',     'cat' => 'Lapiceros',  'costo' => 2.20,  'margen' => 150.00, 'minimo' => 20,
             'desc'   => 'Lapicero metálico acabado dorado. Mecanismo de giro. Superficie grabable con láser.'],

            // ── Viniles ──────────────────────────────────────────────
            ['nombre' => 'Vinil adhesivo brillante A4',  'cat' => 'Viniles',    'costo' => 0.90,  'margen' => 200.00, 'minimo' => 50,
             'desc'   => 'Lámina de vinil adhesivo brillante A4. Para corte en plotter. Adherible a superficies planas y curvas.'],
            ['nombre' => 'Vinil transfer textil A4',     'cat' => 'Viniles',    'costo' => 1.30,  'margen' => 180.00, 'minimo' => 40,
             'desc'   => 'Vinil de transferencia térmica para textiles A4. Aplicación con prensa a 160°C por 15 s.'],

            // ── Bolsas ───────────────────────────────────────────────
            ['nombre' => 'Tote bag lienzo natural',      'cat' => 'Bolsas',     'costo' => 4.50,  'margen' => 120.00, 'minimo' => 10,
             'desc'   => 'Bolsa tote en lienzo natural 100% algodón. 38×42 cm, asa larga 65 cm.'],

            // ── Gorras ───────────────────────────────────────────────
            ['nombre' => 'Gorra trucker 5 paneles',      'cat' => 'Gorras',     'costo' => 6.50,  'margen' => 108.00, 'minimo' => 6,
             'desc'   => 'Gorra trucker 5 paneles. Frente poliéster sublimable. Cierre plástico ajustable.'],

            // ── Accesorios ───────────────────────────────────────────
            ['nombre' => 'Mousepad sublimado 20×24 cm',  'cat' => 'Accesorios', 'costo' => 3.80,  'margen' => 120.00, 'minimo' => 12,
             'desc'   => 'Mousepad con superficie de tela sublimable. Base de caucho antideslizante. 20×24 cm.'],
            ['nombre' => 'Termo acero inoxidable 500 ml','cat' => 'Accesorios', 'costo' => 8.50,  'margen' => 130.00, 'minimo' => 5,
             'desc'   => 'Termo de acero inoxidable doble pared al vacío. 500 ml. Exterior sublimable. Mantiene 12 h.'],
        ];

        foreach ($productos as $p) {
            DB::table('productos')->insert([
                'nombre'          => $p['nombre'],
                'categoria_id'    => $cats[$p['cat']],
                'descripcion'     => $p['desc'],
                'costo_base'      => $p['costo'],
                'margen_ganancia' => $p['margen'],
                'stock_actual'    => 0,
                'stock_reservado' => 0,
                'stock_minimo'    => $p['minimo'],
                'activo'          => 1,
                'created_at'      => $ahora,
                'updated_at'      => $ahora,
            ]);
        }

        $this->command->info('  ' . count($productos) . ' productos insertados.');
    }

    // ----------------------------------------------------------------
    // 4. Dos lotes FIFO por producto con costos distintos
    //    El trigger crea los lotes automáticamente al insertar.
    // ----------------------------------------------------------------
    private function insertarMovimientosIniciales(): void
    {
        $adminId = DB::table('users')->where('rol', 'administrador')->value('id');

        if (! $adminId) {
            $this->command->warn('  No se encontró usuario administrador. Saltando movimientos.');
            return;
        }

        $productos = DB::table('productos')->get();
        $fecha1    = now()->subDays(30);
        $fecha2    = now()->subDays(10);

        foreach ($productos as $p) {
            MovimientoInventario::create([
                'producto_id'    => $p->id,
                'usuario_id'     => $adminId,
                'tipo'           => 'entrada',
                'cantidad'       => max(5, (int) round($p->stock_minimo * 1.5)),
                'costo_unitario' => $p->costo_base,
                'motivo'         => 'Compra inicial — Lote 1',
                'fecha'          => $fecha1,
            ]);

            MovimientoInventario::create([
                'producto_id'    => $p->id,
                'usuario_id'     => $adminId,
                'tipo'           => 'entrada',
                'cantidad'       => max(10, $p->stock_minimo * 3),
                'costo_unitario' => round($p->costo_base * 1.08, 2),
                'motivo'         => 'Reposición — Lote 2 (costo +8%)',
                'fecha'          => $fecha2,
            ]);
        }

        $this->command->info('  2 lotes FIFO × ' . $productos->count() . ' productos (' . ($productos->count() * 2) . ' movimientos).');
    }
}
