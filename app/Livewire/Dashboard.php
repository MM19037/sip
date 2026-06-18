<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $stats          = DB::table('v_dashboard')->first();
        $pedidosActivos = DB::table('v_pedidos_activos')->limit(8)->get();
        $alertas        = DB::table('v_alertas_inventario')->limit(5)->get();

        $ordenesStats = DB::table('ordenes_produccion')
            ->whereNotIn('estado', ['completado'])
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Gráfica 1: pedidos por estado (donut)
        $pedidosPorEstado = DB::table('pedidos')
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(fn($r) => [$r->estado => (int) $r->total]);

        // Gráfica 2: ventas del mes por día (barras)
        $diasMes = now()->daysInMonth;
        $ventasBruto = DB::table('pedidos')
            ->selectRaw('DAY(fecha_entrega) as dia, SUM(total) as total')
            ->where('estado', 'entregado')
            ->whereMonth('fecha_entrega', now()->month)
            ->whereYear('fecha_entrega', now()->year)
            ->groupBy('dia')
            ->pluck('total', 'dia');
        $ventasMes = collect(range(1, $diasMes))
            ->map(fn($d) => round((float) ($ventasBruto[$d] ?? 0), 2))
            ->values()
            ->all();

        // Gráfica 3: entradas/salidas del mes por día (línea)
        $movsBruto = DB::table('movimientos_inventario')
            ->selectRaw('DATE(fecha) as dia, tipo, SUM(cantidad) as total')
            ->whereIn('tipo', ['entrada', 'salida'])
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->groupBy('dia', 'tipo')
            ->get()
            ->groupBy('dia');

        $diasLabels = collect(range(1, $diasMes))->map(fn($d) => str_pad($d, 2, '0', STR_PAD_LEFT))->all();
        $entradas = [];
        $salidas  = [];
        for ($d = 1; $d <= $diasMes; $d++) {
            $key  = now()->format('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
            $rows = $movsBruto->get($key, collect());
            $entradas[] = (int) ($rows->firstWhere('tipo', 'entrada')?->total ?? 0);
            $salidas[]  = (int) ($rows->firstWhere('tipo', 'salida')?->total ?? 0);
        }

        // Gráfica 4: stock actual vs mínimo por categoría (barras agrupadas)
        $stockCategorias = DB::table('productos')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nombre as categoria, SUM(productos.stock_actual) as stock_total, SUM(productos.stock_minimo) as minimo_total')
            ->where('productos.activo', 1)
            ->groupBy('categorias.nombre')
            ->orderBy('categorias.nombre')
            ->get();

        $charts = [
            'pedidosPorEstado' => $pedidosPorEstado,
            'diasLabels'       => $diasLabels,
            'ventasMes'        => $ventasMes,
            'entradas'         => $entradas,
            'salidas'          => $salidas,
            'categorias'       => $stockCategorias->pluck('categoria')->all(),
            'stockActual'      => $stockCategorias->pluck('stock_total')->map(fn($v) => (int) $v)->all(),
            'stockMinimo'      => $stockCategorias->pluck('minimo_total')->map(fn($v) => (int) $v)->all(),
        ];

        return view('livewire.dashboard', compact('stats', 'pedidosActivos', 'alertas', 'charts', 'ordenesStats'));
    }
}
