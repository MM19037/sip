<?php

namespace App\Livewire\Costos;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Costos — Rentabilidad')]
class Rentabilidad extends Component
{
    public int    $anio;
    public ?int   $mes        = null;
    public string $inputDesde = '';
    public string $inputHasta = '';
    public string $desde      = '';
    public string $hasta      = '';

    public function mount(): void
    {
        $this->anio       = now()->year;
        $this->inputDesde = now()->startOfMonth()->format('Y-m-d');
        $this->inputHasta = now()->endOfMonth()->format('Y-m-d');
    }

    public function aplicarFechas(): void
    {
        $this->desde = $this->inputDesde;
        $this->hasta = $this->inputHasta;
    }

    public function limpiarFechas(): void
    {
        $this->desde = '';
        $this->hasta = '';
    }

    public function render(): View
    {
        $filas = DB::table('v_rentabilidad_productos')
            ->when($this->desde && $this->hasta, fn ($q) => $q
                ->whereRaw("CONCAT(anio, '-', LPAD(mes, 2, '0'), '-01') >= ?", [$this->desde])
                ->whereRaw("CONCAT(anio, '-', LPAD(mes, 2, '0'), '-01') <= ?", [$this->hasta]),
                fn ($q) => $q->where('anio', $this->anio)->when($this->mes, fn ($q2) => $q2->where('mes', $this->mes))
            )
            ->get();

        // Agrupar por producto sumando todos los meses del período
        $porProducto = $filas->groupBy('producto_id')->map(function ($rows) {
            $ingresos = $rows->sum('ingresos');
            $costos   = $rows->sum('costos');
            $ganancia = $rows->sum('ganancia_bruta');
            return [
                'producto'            => $rows->first()->producto,
                'categoria'           => $rows->first()->categoria,
                'unidades_vendidas'   => $rows->sum('unidades_vendidas'),
                'precio_prom'         => $rows->sum('unidades_vendidas') > 0
                    ? round($rows->sum('ingresos') / $rows->sum('unidades_vendidas'), 2)
                    : 0,
                'costo_prom_fifo'     => $rows->sum('unidades_vendidas') > 0
                    ? round($rows->sum('costos') / $rows->sum('unidades_vendidas'), 2)
                    : 0,
                'ingresos'            => $ingresos,
                'costos'              => $costos,
                'ganancia'            => $ganancia,
                'margen_pct'          => $ingresos > 0
                    ? round($ganancia / $ingresos * 100, 2)
                    : 0,
            ];
        })->sortByDesc('ganancia')->values();

        // Resumen por categoría
        $porCategoria = $porProducto->groupBy('categoria')->map(fn ($rows) => [
            'categoria'  => $rows->first()['categoria'],
            'ingresos'   => $rows->sum('ingresos'),
            'costos'     => $rows->sum('costos'),
            'ganancia'   => $rows->sum('ganancia'),
            'margen_pct' => $rows->sum('ingresos') > 0
                ? round($rows->sum('ganancia') / $rows->sum('ingresos') * 100, 2)
                : 0,
        ])->sortByDesc('ganancia')->values();

        $totalIngresos = $porProducto->sum('ingresos');
        $totalGanancia = $porProducto->sum('ganancia');

        $resumen = [
            'ingresos'   => $totalIngresos,
            'costos'     => $porProducto->sum('costos'),
            'ganancia'   => $totalGanancia,
            'margen_pct' => $totalIngresos > 0
                ? round($totalGanancia / $totalIngresos * 100, 2)
                : 0,
            'productos'  => $porProducto->count(),
        ];

        $aniosDisponibles = DB::table('v_rentabilidad_productos')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');

        if ($aniosDisponibles->isEmpty()) {
            $aniosDisponibles = collect([now()->year]);
        }

        return view('livewire.costos.rentabilidad', compact(
            'porProducto', 'porCategoria', 'resumen', 'aniosDisponibles'
        ) + ['desde' => $this->desde, 'hasta' => $this->hasta]);
    }
}
