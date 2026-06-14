<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CostosController extends Controller
{
    public function lotes(Request $request): mixed
    {
        $lotes = Lote::with('producto.categoria')
            ->where('activo', true)
            ->orderBy('fecha_entrada')
            ->get()
            ->map(fn($l) => [
                'numero_lote'    => $l->numero_lote,
                'producto'       => $l->producto?->nombre ?? '—',
                'categoria'      => $l->producto?->categoria?->nombre ?? '—',
                'fecha_entrada'  => $l->fecha_entrada->format('d/m/Y'),
                'inicial'        => $l->cantidad_inicial,
                'disponible'     => $l->cantidad_disponible,
                'reservado'      => $l->cantidad_reservada,
                'libre'          => $l->cantidadLibre(),
                'costo_unitario' => $l->costo_unitario,
                'valor_disp'     => round($l->valorDisponible(), 2),
            ]);

        $resumen = [
            'total_lotes'  => $lotes->count(),
            'unidades'     => $lotes->sum('disponible'),
            'valor_total'  => $lotes->sum('valor_disp'),
        ];

        $titulo = 'Reporte de Lotes Activos (FIFO)';
        $fecha  = now()->format('d/m/Y H:i');
        $slug   = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse('lotes_activos', ['N° Lote', 'Producto', 'Categoría', 'Fecha entrada', 'Inicial', 'Disponible', 'Reservado', 'Libre', 'Costo unit.', 'Valor disp.'],
                $lotes->map(fn($r) => [$r['numero_lote'], $r['producto'], $r['categoria'], $r['fecha_entrada'], $r['inicial'], $r['disponible'], $r['reservado'], $r['libre'], $r['costo_unitario'], $r['valor_disp']])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.costos.lotes', compact('lotes', 'resumen', 'titulo', 'fecha'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("lotes_activos_{$slug}.pdf");
    }

    public function rentabilidad(Request $request): mixed
    {
        $anio = (int) $request->query('anio', now()->year);
        $mes  = $request->query('mes') ? (int) $request->query('mes') : null;

        $query = DB::table('v_rentabilidad_productos')
            ->where('anio', $anio)
            ->orderBy('ganancia_bruta', 'desc');

        if ($mes) {
            $query->where('mes', $mes);
        }

        $filas = $query->get()->map(fn($r) => (array) $r);

        $resumen = [
            'ingresos' => $filas->sum('ingresos'),
            'costos'   => $filas->sum('costos'),
            'ganancia' => $filas->sum('ganancia_bruta'),
            'margen'   => $filas->sum('ingresos') > 0
                ? round($filas->sum('ganancia_bruta') / $filas->sum('ingresos') * 100, 1)
                : 0,
        ];

        $periodo = $mes
            ? \Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y')
            : "Año $anio";

        $titulo = "Rentabilidad por Producto — $periodo";
        $fecha  = now()->format('d/m/Y H:i');
        $slug   = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse("rentabilidad_{$anio}" . ($mes ? "_m{$mes}" : ''),
                ['Producto', 'Categoría', 'Unidades vendidas', 'Precio prom.', 'Costo prom. FIFO', 'Ingresos', 'Costos', 'Ganancia', 'Margen %'],
                $filas->map(fn($r) => [$r['producto'], $r['categoria'], $r['unidades_vendidas'], $r['precio_promedio_venta'], $r['costo_promedio_fifo'], $r['ingresos'], $r['costos'], $r['ganancia_bruta'], $r['margen_pct']])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.costos.rentabilidad', compact('filas', 'resumen', 'titulo', 'fecha', 'periodo'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("rentabilidad_{$slug}.pdf");
    }

    private function csvResponse(string $nombre, array $cabeceras, array $filas): Response
    {
        $callback = function () use ($cabeceras, $filas) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
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
