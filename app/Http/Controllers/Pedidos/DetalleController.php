<?php

namespace App\Http\Controllers\Pedidos;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DetalleController extends Controller
{
    public function pdf(Pedido $pedido): Response
    {
        $pedido->load(['cliente', 'usuario', 'detalles.producto', 'ordenProduccion.operario']);

        $titulo = "Pedido #{$pedido->id}";
        $fecha  = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reportes.pedidos.detalle', compact('pedido', 'titulo', 'fecha'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("pedido_{$pedido->id}.pdf");
    }
}
