<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PedidosController extends Controller
{
    public function index(Request $request): mixed
    {
        $estado   = $request->query('estado');
        $busqueda = $request->query('busqueda');
        $desde    = $request->query('desde');
        $hasta    = $request->query('hasta');

        $query = Pedido::with(['cliente', 'usuario'])
            ->orderBy('fecha_pedido', 'desc');

        if ($estado) {
            $query->where('estado', $estado);
        }
        if ($busqueda) {
            $query->whereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$busqueda}%"));
        }
        if ($desde) {
            $query->whereDate('fecha_pedido', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha_pedido', '<=', $hasta);
        }

        $pedidos = $query->get()->map(fn($p) => [
            'id'              => $p->id,
            'cliente'         => $p->cliente->nombre,
            'estado'          => $p->estadoLabel(),
            'fecha_prometida' => $p->fecha_prometida?->format('d/m/Y') ?? '—',
            'total'           => $p->total,
            'ganancia'        => $p->ganancia,
            'usuario'         => $p->usuario->name,
            'fecha_pedido'    => $p->fecha_pedido->format('d/m/Y'),
        ]);

        $resumen = [
            'total_pedidos' => $pedidos->count(),
            'ingresos'      => $pedidos->sum('total'),
            'ganancia'      => $pedidos->sum('ganancia'),
        ];

        $filtros = array_filter([
            'Estado'   => $estado,
            'Cliente'  => $busqueda,
            'Desde'    => $desde,
            'Hasta'    => $hasta,
        ]);

        $titulo = 'Reporte de Pedidos';
        $fecha  = now()->format('d/m/Y H:i');
        $slug   = now()->format('Ymd_His');

        if ($request->query('formato') === 'csv') {
            return $this->csvResponse(
                'pedidos',
                ['#', 'Cliente', 'Estado', 'Fecha pedido', 'Fecha prometida', 'Total', 'Ganancia', 'Registrado por'],
                $pedidos->map(fn($p) => [
                    $p['id'], $p['cliente'], $p['estado'], $p['fecha_pedido'],
                    $p['fecha_prometida'], $p['total'], $p['ganancia'], $p['usuario'],
                ])->all()
            );
        }

        $pdf = Pdf::loadView('reportes.pedidos.lista', compact('pedidos', 'resumen', 'filtros', 'titulo', 'fecha'))
            ->setPaper('letter', 'landscape');

        return $pdf->stream("pedidos_{$slug}.pdf");
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
