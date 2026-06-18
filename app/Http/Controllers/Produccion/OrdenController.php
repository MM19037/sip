<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Models\OrdenProduccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class OrdenController extends Controller
{
    public function pdf(OrdenProduccion $orden): Response
    {
        $orden->load(['pedido.cliente', 'pedido.detalles.producto', 'operario']);

        $titulo = "Orden de Producción #OP{$orden->id}";
        $fecha  = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reportes.produccion.orden', compact('orden', 'titulo', 'fecha'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("orden_produccion_{$orden->id}.pdf");
    }
}
