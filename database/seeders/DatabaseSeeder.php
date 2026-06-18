<?php

namespace Database\Seeders;

use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermisoRolSeeder::class);

        $ahora = now();

        // ── 1. Usuario administrador ──────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@sistema.local'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('Admin123!'),
                'rol'      => 'administrador',
                'activo'   => true,
            ]
        );

        // ── 2. Clientes de ejemplo ────────────────────────────────────
        $clientes = [
            ['nombre' => 'María González',   'telefono' => '555-1001', 'email' => 'maria@ejemplo.com',    'direccion' => 'Av. Principal 101'],
            ['nombre' => 'Carlos Ramírez',   'telefono' => '555-1002', 'email' => 'carlos@ejemplo.com',   'direccion' => 'Calle 5 #22-10'],
            ['nombre' => 'Empresa Creativa', 'telefono' => '555-1003', 'email' => 'compras@creativa.com', 'direccion' => 'Zona Industrial, Bodega 4'],
        ];

        foreach ($clientes as $c) {
            DB::table('clientes')->updateOrInsert(
                ['nombre' => $c['nombre']],
                array_merge($c, ['created_at' => $ahora, 'updated_at' => $ahora])
            );
        }

        // ── 3. Categorías ─────────────────────────────────────────────
        $definicion = [
            'Tazas'      => 'Tazas cerámicas y de colores para sublimación e impresión personalizada.',
            'Camisetas'  => 'Prendas de algodón y poliéster para transfer, sublimación y bordado.',
            'Lapiceros'  => 'Bolígrafos metálicos y plásticos para grabado láser y serigrafía.',
            'Viniles'    => 'Láminas de vinil adhesivo y transfer para corte en plotter y prensa.',
            'Bolsas'     => 'Tote bags y bolsas de lienzo para serigrafía y sublimación.',
            'Gorras'     => 'Gorras y sombreros para sublimación y bordado computarizado.',
            'Accesorios' => 'Mousepads, termos, llaveros y otros artículos promocionales sublimables.',
        ];

        $cats = [];
        foreach ($definicion as $nombre => $desc) {
            DB::table('categorias')->updateOrInsert(
                ['nombre' => $nombre],
                ['descripcion' => $desc, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora]
            );
            $cats[$nombre] = DB::table('categorias')->where('nombre', $nombre)->value('id');
        }

        // ── 4. Productos + stock inicial vía movimientos (genera lotes FIFO) ──
        $productos = [
            ['nombre' => 'Taza cerámica blanca 11oz',  'cat' => 'Tazas',      'costo' => 5.00, 'margen' => 120.00, 'minimo' => 10, 'stock' => 50],
            ['nombre' => 'Taza mágica cambia color',   'cat' => 'Tazas',      'costo' => 8.00, 'margen' => 100.00, 'minimo' => 8,  'stock' => 30],
            ['nombre' => 'Camiseta algodón talla M',   'cat' => 'Camisetas',  'costo' => 7.00, 'margen' => 100.00, 'minimo' => 10, 'stock' => 40],
            ['nombre' => 'Camiseta algodón talla L',   'cat' => 'Camisetas',  'costo' => 7.00, 'margen' => 100.00, 'minimo' => 10, 'stock' => 40],
            ['nombre' => 'Lapicero metálico grabado',  'cat' => 'Lapiceros',  'costo' => 1.50, 'margen' => 150.00, 'minimo' => 20, 'stock' => 100],
            ['nombre' => 'Vinil adhesivo A4',          'cat' => 'Viniles',    'costo' => 0.80, 'margen' => 200.00, 'minimo' => 30, 'stock' => 200],
            ['nombre' => 'Vinil transfer textil',      'cat' => 'Viniles',    'costo' => 1.20, 'margen' => 180.00, 'minimo' => 25, 'stock' => 150],
            ['nombre' => 'Tote bag lienzo',            'cat' => 'Bolsas',     'costo' => 4.00, 'margen' => 125.00, 'minimo' => 8,  'stock' => 35],
            ['nombre' => 'Gorra bordada ajustable',    'cat' => 'Gorras',     'costo' => 6.00, 'margen' => 110.00, 'minimo' => 5,  'stock' => 25],
            ['nombre' => 'Mousepad sublimado 20x24cm', 'cat' => 'Accesorios', 'costo' => 3.50, 'margen' => 130.00, 'minimo' => 10, 'stock' => 45],
        ];

        foreach ($productos as $p) {
            if (DB::table('productos')->where('nombre', $p['nombre'])->exists()) {
                continue;
            }

            $id = DB::table('productos')->insertGetId([
                'nombre'          => $p['nombre'],
                'categoria_id'    => $cats[$p['cat']],
                'costo_base'      => $p['costo'],
                'margen_ganancia' => $p['margen'],
                'stock_actual'    => 0,
                'stock_reservado' => 0,
                'stock_minimo'    => $p['minimo'],
                'activo'          => 1,
                'created_at'      => $ahora,
                'updated_at'      => $ahora,
            ]);

            // El trigger trg_movimiento_insert actualiza stock_actual y crea el lote FIFO
            MovimientoInventario::create([
                'producto_id'    => $id,
                'usuario_id'     => $admin->id,
                'tipo'           => 'entrada',
                'cantidad'       => $p['stock'],
                'costo_unitario' => $p['costo'],
                'motivo'         => 'Stock inicial',
                'fecha'          => $ahora,
            ]);
        }
    }
}
