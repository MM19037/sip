<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PermisoRol extends Model
{
    protected $table = 'permisos_rol';

    protected $fillable = ['rol', 'seccion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    const SECCIONES = [
        'pedidos'                => 'Pedidos',
        'inventario'             => 'Inventario',
        'inventario.solicitudes' => 'Solicitudes de stock',
        'clientes'               => 'Clientes',
        'produccion'             => 'Producción',
        'costos'                 => 'Costos y Rentabilidad',
        'categorias'             => 'Categorías',
    ];

    const GRUPOS = [
        'Recepción'      => ['pedidos', 'clientes'],
        'Inventario'     => ['inventario', 'inventario.solicitudes', 'categorias'],
        'Producción'     => ['produccion'],
        'Costos'         => ['costos'],
    ];

    const ROLES_CONFIGURABLES = ['recepcionista', 'produccion'];

    // Qué secciones puede controlar cada rol (según las rutas definidas)
    const SECCIONES_POR_ROL = [
        'recepcionista' => ['pedidos', 'inventario', 'clientes'],
        'produccion'    => ['produccion'],
    ];

    public static function tiene(string $rol, string $seccion): bool
    {
        $permisos = Cache::remember(
            "permisos_rol_{$rol}",
            now()->addMinutes(10),
            fn () => static::where('rol', $rol)
                ->pluck('activo', 'seccion')
                ->toArray()
        );

        return (bool) ($permisos[$seccion] ?? true);
    }

    public static function limpiarCache(string $rol): void
    {
        Cache::forget("permisos_rol_{$rol}");
    }
}
