<?php

namespace Database\Seeders;

use App\Models\PermisoRol;
use Illuminate\Database\Seeder;

class PermisoRolSeeder extends Seeder
{
    public function run(): void
    {
        // Eliminar secciones obsoletas que ya no existen en el modelo
        PermisoRol::whereNotIn('seccion', array_keys(PermisoRol::SECCIONES))->delete();

        $defaults = [
            'recepcionista' => [
                'pedidos'                => true,
                'inventario'             => true,
                'inventario.solicitudes' => false,
                'clientes'               => true,
                'produccion'             => false,
                'costos'                 => false,
                'categorias'             => false,
            ],
            'produccion' => [
                'pedidos'                => false,
                'inventario'             => false,
                'inventario.solicitudes' => false,
                'clientes'               => false,
                'produccion'             => true,
                'costos'                 => false,
                'categorias'             => false,
            ],
        ];

        foreach ($defaults as $rol => $secciones) {
            foreach ($secciones as $seccion => $activo) {
                PermisoRol::updateOrCreate(
                    ['rol' => $rol, 'seccion' => $seccion],
                    ['activo' => $activo]
                );
            }

            PermisoRol::limpiarCache($rol);
        }
    }
}
