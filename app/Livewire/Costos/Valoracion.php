<?php

namespace App\Livewire\Costos;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Costos — Valoración de inventario')]
class Valoracion extends Component
{
    public function render(): View
    {
        $porProducto = DB::table('v_valoracion_inventario')->get();

        $porCategoria = $porProducto->groupBy('categoria')->map(fn ($filas) => [
            'categoria'         => $filas->first()->categoria,
            'productos'         => $filas->count(),
            'unidades_en_lotes' => $filas->sum('unidades_en_lotes'),
            'valor_total_fifo'  => $filas->sum('valor_total_fifo'),
            'lotes_activos'     => $filas->sum('lotes_activos'),
        ])->sortByDesc('valor_total_fifo')->values();

        $resumen = [
            'valor_total'          => $porProducto->sum('valor_total_fifo'),
            'valor_reservado'      => DB::table('lotes')
                ->where('activo', true)
                ->selectRaw('COALESCE(SUM(cantidad_reservada * costo_unitario), 0) AS v')
                ->value('v') ?? 0,
            'valor_libre'          => DB::table('lotes')
                ->where('activo', true)
                ->selectRaw('COALESCE(SUM((cantidad_disponible - cantidad_reservada) * costo_unitario), 0) AS v')
                ->value('v') ?? 0,
            'lotes_activos'        => $porProducto->sum('lotes_activos'),
            'productos_con_stock'  => $porProducto->where('unidades_en_lotes', '>', 0)->count(),
            'productos_sin_stock'  => $porProducto->where('unidades_en_lotes', '<=', 0)->count(),
        ];

        return view('livewire.costos.valoracion', compact('porProducto', 'porCategoria', 'resumen'));
    }
}
