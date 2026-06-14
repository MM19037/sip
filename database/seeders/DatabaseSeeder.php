<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador por defecto
        User::firstOrCreate(
            ['email' => 'admin@sistema.local'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('Admin123!'),
                'rol'      => 'administrador',
                'activo'   => true,
            ]
        );

        // Categorías base
        $ahora = now();
        $cats  = [];
        foreach ([
            'Tazas'      => 'Tazas cerámicas y de colores para sublimación e impresión personalizada.',
            'Camisetas'  => 'Prendas de algodón y poliéster para transfer, sublimación y bordado.',
            'Lapiceros'  => 'Bolígrafos metálicos y plásticos para grabado láser y serigrafía.',
            'Viniles'    => 'Láminas de vinil adhesivo y transfer para corte en plotter y prensa.',
            'Bolsas'     => 'Tote bags y bolsas de lienzo para serigrafía y sublimación.',
            'Gorras'     => 'Gorras y sombreros para sublimación y bordado computarizado.',
            'Accesorios' => 'Mousepads, termos, llaveros y otros artículos promocionales sublimables.',
        ] as $nombre => $desc) {
            $cats[$nombre] = DB::table('categorias')->insertGetId([
                'nombre'      => $nombre,
                'descripcion' => $desc,
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);
        }

        // Productos de ejemplo con categoria_id
        DB::table('productos')->insert([
            ['nombre' => 'Taza cerámica blanca 11oz',  'categoria_id' => $cats['Tazas'],      'costo_base' => 5.00,  'margen_ganancia' => 120.00, 'stock_actual' => 50,  'stock_minimo' => 10, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Taza mágica cambia color',   'categoria_id' => $cats['Tazas'],      'costo_base' => 8.00,  'margen_ganancia' => 100.00, 'stock_actual' => 30,  'stock_minimo' => 8,  'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Camiseta algodón talla M',   'categoria_id' => $cats['Camisetas'],  'costo_base' => 7.00,  'margen_ganancia' => 100.00, 'stock_actual' => 40,  'stock_minimo' => 10, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Camiseta algodón talla L',   'categoria_id' => $cats['Camisetas'],  'costo_base' => 7.00,  'margen_ganancia' => 100.00, 'stock_actual' => 40,  'stock_minimo' => 10, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Lapicero metálico grabado',  'categoria_id' => $cats['Lapiceros'],  'costo_base' => 1.50,  'margen_ganancia' => 150.00, 'stock_actual' => 100, 'stock_minimo' => 20, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Vinil adhesivo A4',          'categoria_id' => $cats['Viniles'],    'costo_base' => 0.80,  'margen_ganancia' => 200.00, 'stock_actual' => 200, 'stock_minimo' => 30, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Vinil transfer textil',      'categoria_id' => $cats['Viniles'],    'costo_base' => 1.20,  'margen_ganancia' => 180.00, 'stock_actual' => 150, 'stock_minimo' => 25, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Tote bag lienzo',            'categoria_id' => $cats['Bolsas'],     'costo_base' => 4.00,  'margen_ganancia' => 125.00, 'stock_actual' => 35,  'stock_minimo' => 8,  'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Gorra bordada ajustable',    'categoria_id' => $cats['Gorras'],     'costo_base' => 6.00,  'margen_ganancia' => 110.00, 'stock_actual' => 25,  'stock_minimo' => 5,  'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Mousepad sublimado 20x24cm', 'categoria_id' => $cats['Accesorios'], 'costo_base' => 3.50,  'margen_ganancia' => 130.00, 'stock_actual' => 45,  'stock_minimo' => 10, 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }
}
