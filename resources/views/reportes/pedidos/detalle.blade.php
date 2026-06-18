<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 16mm 18mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

    .header { background: #0f2744; color: #fff; padding: 14px 20px; display: table; width: 100%; margin-bottom: 14px; }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-left .sistema { font-size: 8px; color: #93c5fd; letter-spacing: 1px; text-transform: uppercase; }
    .header-left .titulo { font-size: 15px; font-weight: 700; margin-top: 2px; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 8px; color: #93c5fd; }
    .page-body { padding: 0 10px; }

    .estado-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-left: 8px; vertical-align: middle; }
    .badge-pendiente      { background: #fef9c3; color: #854d0e; }
    .badge-en_produccion  { background: #dbeafe; color: #1e40af; }
    .badge-listo          { background: #dcfce7; color: #166534; }
    .badge-entregado      { background: #d1fae5; color: #065f46; }
    .badge-cancelado      { background: #fee2e2; color: #991b1b; }
    .badge-esperando_stock{ background: #ffedd5; color: #9a3412; }

    .info-grid { display: table; width: 100%; margin-bottom: 14px; border-spacing: 8px 0; }
    .info-col  { display: table-cell; width: 50%; vertical-align: top; }
    .card { border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 12px; margin-bottom: 8px; }
    .card-title { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 8px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
    .info-label { color: #64748b; }
    .info-value { font-weight: 600; text-align: right; }
    .cliente-nombre { font-size: 13px; font-weight: 700; margin-bottom: 4px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead tr { background: #0f2744; color: #fff; }
    thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 600; }
    thead th.r { text-align: right; }
    thead th.c { text-align: center; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tbody td.bold { font-weight: 700; }
    .custom-desc { font-size: 8px; color: #94a3b8; margin-top: 1px; }

    .totales { display: table; width: 280px; margin-left: auto; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; }
    .totales-row { display: table-row; }
    .totales-row td { display: table-cell; padding: 5px 12px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
    .totales-row.total { background: #0f2744; color: #fff; }
    .totales-row.total td { font-size: 11px; font-weight: 700; border-bottom: none; }
    .totales-row .tl { color: #64748b; }
    .totales-row.total .tl { color: #93c5fd; }
    .totales-row .tv { text-align: right; font-family: monospace; }
    .red   { color: #dc2626; }
    .green { color: #16a34a; }

    .produccion-card { border: 1px solid #bfdbfe; border-radius: 4px; padding: 10px 12px; background: #eff6ff; margin-top: 14px; }
    .produccion-card .card-title { color: #1e40af; border-color: #bfdbfe; }

    .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: right; }

    @php
        $badgeClass = match($pedido->estado) {
            'pendiente'       => 'badge-pendiente',
            'en_produccion'   => 'badge-en_produccion',
            'listo'           => 'badge-listo',
            'entregado'       => 'badge-entregado',
            'cancelado'       => 'badge-cancelado',
            default           => 'badge-esperando_stock',
        };
    @endphp
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="sistema">SIP — Sistema Integral de Pedidos</div>
        <div class="titulo">
            Pedido #{{ $pedido->id }}
            <span class="estado-badge {{ $badgeClass }}">{{ $pedido->estadoLabel() }}</span>
        </div>
    </div>
    <div class="header-right">
        Generado: {{ $fecha }}<br>
        Registrado: {{ $pedido->fecha_pedido->format('d/m/Y H:i') }}
    </div>
</div>

<div class="page-body">
{{-- Info cliente + datos pedido --}}
<div class="info-grid">
    <div class="info-col" style="padding-right: 8px;">
        <div class="card">
            <div class="card-title">Cliente</div>
            <div class="cliente-nombre">{{ $pedido->cliente->nombre }}</div>
            @if($pedido->cliente->telefono)
                <div style="margin-bottom:3px; color:#475569;">{{ $pedido->cliente->telefono }}</div>
            @endif
            @if($pedido->cliente->email)
                <div style="margin-bottom:3px; color:#475569;">{{ $pedido->cliente->email }}</div>
            @endif
            @if($pedido->cliente->direccion)
                <div style="color:#475569;">{{ $pedido->cliente->direccion }}</div>
            @endif
        </div>
    </div>
    <div class="info-col" style="padding-left: 8px;">
        <div class="card">
            <div class="card-title">Datos del pedido</div>
            <div class="info-row">
                <span class="info-label">Registrado por</span>
                <span class="info-value">{{ $pedido->usuario->name }}</span>
            </div>
            @if($pedido->fecha_prometida)
                <div class="info-row">
                    <span class="info-label">Fecha prometida</span>
                    <span class="info-value {{ $pedido->fecha_prometida->isPast() && !in_array($pedido->estado, ['entregado','cancelado']) ? 'red' : '' }}">
                        {{ $pedido->fecha_prometida->format('d/m/Y') }}
                    </span>
                </div>
            @endif
            @if($pedido->fecha_entrega)
                <div class="info-row">
                    <span class="info-label">Entregado el</span>
                    <span class="info-value green">{{ $pedido->fecha_entrega->format('d/m/Y H:i') }}</span>
                </div>
            @endif
            @if($pedido->notas)
                <div style="margin-top:6px; padding-top:6px; border-top:1px solid #e2e8f0; color:#475569;">
                    <div style="font-weight:600; margin-bottom:2px;">Notas:</div>
                    {{ $pedido->notas }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Productos --}}
<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th class="c">Cantidad</th>
            <th class="r">Precio unitario</th>
            <th class="r">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pedido->detalles as $d)
        <tr>
            <td class="bold">
                {{ $d->producto->nombre }}
                @if($d->descripcion_custom)
                    <div class="custom-desc">{{ $d->descripcion_custom }}</div>
                @endif
            </td>
            <td class="c">{{ $d->cantidad }}</td>
            <td class="r">${{ number_format($d->precio_unitario, 2) }}</td>
            <td class="r bold">${{ number_format($d->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totales --}}
<div class="totales">
    <div class="totales-row">
        <td class="tl">Subtotal</td>
        <td class="tv">${{ number_format($pedido->subtotal, 2) }}</td>
    </div>
    @if($pedido->descuento > 0)
    <div class="totales-row">
        <td class="tl">Descuento</td>
        <td class="tv red">-${{ number_format($pedido->descuento, 2) }}</td>
    </div>
    @endif
    <div class="totales-row total">
        <td class="tl">TOTAL</td>
        <td class="tv">${{ number_format($pedido->total, 2) }}</td>
    </div>
</div>

{{-- Orden de producción (si existe) --}}
@if($pedido->ordenProduccion)
    @php $op = $pedido->ordenProduccion; @endphp
    <div class="produccion-card">
        <div class="card-title">Orden de Producción #OP{{ $op->id }}</div>
        <div class="info-grid">
            <div class="info-col" style="padding-right:8px;">
                <div class="info-row">
                    <span class="info-label">Estado</span>
                    <span class="info-value">{{ $op->estadoLabel() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Prioridad</span>
                    <span class="info-value">{{ $op->prioridadLabel() }}</span>
                </div>
            </div>
            <div class="info-col" style="padding-left:8px;">
                @if($op->operario)
                    <div class="info-row">
                        <span class="info-label">Operario</span>
                        <span class="info-value">{{ $op->operario->name }}</span>
                    </div>
                @endif
                @if($op->tiempo_minutos)
                    <div class="info-row">
                        <span class="info-label">Tiempo</span>
                        <span class="info-value">{{ $op->tiempoTranscurrido() }}</span>
                    </div>
                @endif
            </div>
        </div>
        @if($op->observaciones)
            <div style="margin-top:6px; color:#1e40af;">{{ $op->observaciones }}</div>
        @endif
    </div>
@endif

<div class="footer">
    SIP — Sistema Integral de Pedidos &nbsp;|&nbsp; {{ $fecha }}
</div>
</div>{{-- .page-body --}}

</body>
</html>
