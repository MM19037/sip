<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\OrdenProduccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProduccionController extends Controller
{
    public function index(Request $request): mixed
    {
        $estado = $request->query('estado');
        $desde  = $request->query('desde');
        $hasta  = $request->query('hasta');

        $query = OrdenProduccion::with(['pedido.cliente', 'operario'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($desde,  fn ($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta,  fn ($q) => $q->whereDate('created_at', '<=', $hasta))
            ->orderBy('prioridad')
            ->orderBy('created_at');

        $ordenes = $query->get()->map(fn ($o) => [
            'id'              => $o->id,
            'pedido_id'       => $o->pedido_id,
            'cliente'         => $o->pedido->cliente->nombre,
            'estado'          => $o->estadoLabel(),
            'prioridad'       => $o->prioridadLabel(),
            'operario'        => $o->operario?->name ?? '—',
            'fecha_inicio'    => $o->fecha_inicio?->format('d/m/Y H:i') ?? '—',
            'tiempo'          => $o->tiempoTranscurrido(),
            'fecha_prometida' => $o->pedido->fecha_prometida?->format('d/m/Y') ?? '—',
            'observaciones'   => $o->observaciones ?? '',
        ]);

        $resumen = [
            'total'      => $ordenes->count(),
            'asignadas'  => $ordenes->where('estado', 'Asignado')->count(),
            'en_proceso' => $ordenes->where('estado', 'En proceso')->count(),
            'pausadas'   => $ordenes->where('estado', 'Pausado')->count(),
        ];

        $filtros = array_filter([
            'Estado' => $estado,
            'Desde'  => $desde,
            'Hasta'  => $hasta,
        ]);

        $titulo = 'Reporte de Órdenes de Producción';
        $fecha  = now()->format('d/m/Y H:i');
        $slug   = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse(
                'ordenes_produccion',
                ['#OP', 'Pedido', 'Cliente', 'Estado', 'Prioridad', 'Operario', 'Fecha inicio', 'Tiempo', 'Fecha prometida', 'Observaciones'],
                $ordenes->map(fn ($o) => [
                    $o['id'], $o['pedido_id'], $o['cliente'], $o['estado'],
                    $o['prioridad'], $o['operario'], $o['fecha_inicio'],
                    $o['tiempo'], $o['fecha_prometida'], $o['observaciones'],
                ])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.produccion.lista', compact('ordenes', 'resumen', 'filtros', 'titulo', 'fecha'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("ordenes_produccion_{$slug}.pdf");
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
