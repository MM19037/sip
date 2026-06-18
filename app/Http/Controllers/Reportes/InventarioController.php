<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function productos(Request $request): mixed
    {
        $productos = Producto::with('categoria')
            ->orderBy('nombre')
            ->get()
            ->map(fn($p) => [
                'nombre'       => $p->nombre,
                'categoria'    => $p->categoria?->nombre ?? '—',
                'costo_base'   => $p->costo_base,
                'precio_venta' => round($p->costo_base * (1 + $p->margen_ganancia / 100), 2),
                'stock_actual' => $p->stock_actual,
                'reservado'    => $p->stock_reservado,
                'disponible'   => $p->stock_actual - $p->stock_reservado,
                'minimo'       => $p->stock_minimo,
                'activo'       => $p->activo,
            ]);

        $titulo  = 'Reporte de Productos';
        $fecha   = now()->format('d/m/Y H:i');
        $slug    = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse('productos', ['Producto', 'Categoría', 'Costo base', 'Precio venta', 'Stock actual', 'Reservado', 'Disponible', 'Mínimo', 'Activo'],
                $productos->map(fn($r) => [$r['nombre'], $r['categoria'], $r['costo_base'], $r['precio_venta'], $r['stock_actual'], $r['reservado'], $r['disponible'], $r['minimo'], $r['activo'] ? 'Sí' : 'No'])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.inventario.productos', compact('productos', 'titulo', 'fecha'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("productos_{$slug}.pdf");
    }

    public function movimientos(Request $request): mixed
    {
        $desde = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->query('hasta', now()->format('Y-m-d'));

        $movimientos = MovimientoInventario::with(['producto', 'usuario', 'lote'])
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta,  fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($m) => [
                'lote'     => $m->lote?->numero_lote ?? '—',
                'fecha'    => $m->fecha->format('d/m/Y H:i'),
                'producto' => $m->producto?->nombre ?? '—',
                'tipo'     => $m->tipoLabel(),
                'cantidad' => $m->cantidad,
                'costo'    => $m->costo_unitario,
                'motivo'   => $m->motivo ?? '—',
                'usuario'  => $m->usuario?->name ?? '—',
            ]);

        $titulo = 'Reporte de Movimientos de Inventario';
        $fecha  = now()->format('d/m/Y H:i');
        $rango  = \Carbon\Carbon::parse($desde)->format('d/m/Y') . ' — ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y');
        $slug   = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse('movimientos', ['N° Lote', 'Fecha', 'Producto', 'Tipo', 'Cantidad', 'Costo unit.', 'Motivo', 'Usuario'],
                $movimientos->map(fn($r) => [$r['lote'], $r['fecha'], $r['producto'], $r['tipo'], $r['cantidad'], $r['costo'], $r['motivo'], $r['usuario']])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.inventario.movimientos', compact('movimientos', 'titulo', 'fecha', 'rango', 'desde', 'hasta'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("movimientos_{$slug}.pdf");
    }

    public function valoracion(Request $request): mixed
    {
        $filas = DB::table('v_valoracion_inventario')
            ->orderBy('categoria')
            ->orderBy('producto')
            ->get()
            ->map(fn($r) => (array) $r);

        $resumen = [
            'valor_total'    => $filas->sum('valor_total_fifo'),
            'lotes_activos'  => $filas->sum('lotes_activos'),
            'productos'      => $filas->count(),
        ];

        $titulo = 'Valoración de Inventario (FIFO)';
        $fecha  = now()->format('d/m/Y H:i');
        $slug   = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse('valoracion_fifo', ['Categoría', 'Producto', 'Stock actual', 'Reservado', 'Libre', 'Lotes activos', 'Costo prom. FIFO', 'Valor FIFO'],
                $filas->map(fn($r) => [$r['categoria'], $r['producto'], $r['stock_actual'], $r['stock_reservado'], $r['stock_libre'], $r['lotes_activos'], $r['costo_promedio_fifo'], $r['valor_total_fifo']])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.inventario.valoracion', compact('filas', 'resumen', 'titulo', 'fecha'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("valoracion_fifo_{$slug}.pdf");
    }

    private function csvResponse(string $nombre, array $cabeceras, array $filas): Response
    {
        $callback = function () use ($cabeceras, $filas) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($handle, $cabeceras);
            foreach ($filas as $fila) {
                fputcsv($handle, $fila);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombre}_" . now()->format('Ymd_His') . '.csv"',
        ]);
    }
}
