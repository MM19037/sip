<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 16mm 18mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

    .header { background: #0f2744; color: #fff; padding: 14px 20px; display: table; width: 100%; margin-bottom: 0; }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-left .sistema { font-size: 8px; color: #93c5fd; letter-spacing: 1px; text-transform: uppercase; }
    .header-left .titulo { font-size: 15px; font-weight: 700; margin-top: 2px; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 8px; color: #93c5fd; }

    .estado-bar { display: table; width: 100%; margin-bottom: 14px; }
    .estado-cell { display: table-cell; padding: 6px 20px; font-size: 9px; font-weight: 700; }
    .estado-produccion { background: #dbeafe; color: #1e40af; }
    .estado-asignado   { background: #e0e7ff; color: #3730a3; }
    .estado-completado { background: #dcfce7; color: #166534; }
    .estado-pausado    { background: #fef3c7; color: #92400e; }
    .prio-alta   { background: #fee2e2; color: #991b1b; }
    .prio-normal { background: #dbeafe; color: #1e40af; }
    .prio-baja   { background: #f1f5f9; color: #475569; }
    .entrega-cell { background: #f8fafc; color: #64748b; text-align: right; }
    .entrega-cell .fecha { font-size: 12px; font-weight: 700; color: #1e293b; }
    .entrega-vencida .fecha { color: #dc2626; }

    .page-body { padding: 0 10px; }

    .info-grid { display: table; width: 100%; margin-bottom: 14px; }
    .info-col  { display: table-cell; vertical-align: top; }
    .card { border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 12px; }
    .card-title { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 8px; }
    .info-row { display: table; width: 100%; margin-bottom: 4px; }
    .info-label { display: table-cell; color: #64748b; width: 45%; }
    .info-value { display: table-cell; font-weight: 600; text-align: right; }
    .cliente-nombre { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
    .red   { color: #dc2626; }
    .green { color: #16a34a; }

    table.productos { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.productos thead tr { background: #0f2744; color: #fff; }
    table.productos thead th { padding: 7px 10px; text-align: left; font-size: 10px; font-weight: 600; }
    table.productos thead th.c { text-align: center; }
    table.productos tbody tr:nth-child(even) { background: #f8fafc; }
    table.productos tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    table.productos tbody td.c { text-align: center; }
    table.productos tbody td.bold { font-weight: 700; }
    .custom-desc { font-size: 8px; color: #f97316; margin-top: 2px; font-weight: 600; }

    .notas-box { border: 2px dashed #fbbf24; border-radius: 4px; padding: 10px 12px; background: #fffbeb; margin-bottom: 14px; }
    .notas-box .titulo { font-size: 9px; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 4px; }
    .notas-box .texto { color: #78350f; }

    .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: right; }

    @php
        $estadoClass = match($orden->estado) {
            'en_proceso' => 'estado-produccion',
            'asignado'   => 'estado-asignado',
            'completado' => 'estado-completado',
            'pausado'    => 'estado-pausado',
            default      => 'estado-asignado',
        };
        $prioClass = match($orden->prioridad) {
            1 => 'prio-alta',
            3 => 'prio-baja',
            default => 'prio-normal',
        };
        $pedido = $orden->pedido;
        $vencida = $pedido->fecha_prometida && $pedido->fecha_prometida->isPast()
            && !in_array($pedido->estado, ['entregado', 'cancelado']);
    @endphp
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="sistema">SIP — Sistema Integral de Pedidos</div>
        <div class="titulo">Orden de Producción #OP{{ $orden->id }}</div>
    </div>
    <div class="header-right">
        Generado: {{ $fecha }}<br>
        Pedido #{{ $pedido->id }}
    </div>
</div>

<div class="estado-bar">
    <div class="estado-cell {{ $estadoClass }}">
        Estado: {{ $orden->estadoLabel() }}
    </div>
    <div class="estado-cell {{ $prioClass }}">
        Prioridad: {{ $orden->prioridadLabel() }}
    </div>
    <div class="estado-cell entrega-cell {{ $vencida ? 'entrega-vencida' : '' }}">
        Entrega prometida<br>
        <span class="fecha">
            {{ $pedido->fecha_prometida ? $pedido->fecha_prometida->format('d/m/Y') : '—' }}
        </span>
    </div>
</div>

<div class="page-body">

    {{-- Info cliente + producción --}}
    <div class="info-grid" style="margin-bottom: 14px;">
        <div class="info-col" style="padding-right: 8px;">
            <div class="card">
                <div class="card-title">Cliente</div>
                <div class="cliente-nombre">{{ $pedido->cliente->nombre }}</div>
                @if($pedido->cliente->telefono)
                    <div style="color:#475569; margin-bottom:2px;">{{ $pedido->cliente->telefono }}</div>
                @endif
                @if($pedido->cliente->direccion)
                    <div style="color:#475569;">{{ $pedido->cliente->direccion }}</div>
                @endif
            </div>
        </div>
        <div class="info-col" style="padding-left: 8px;">
            <div class="card">
                <div class="card-title">Asignación</div>
                <div class="info-row">
                    <span class="info-label">Operario</span>
                    <span class="info-value">{{ $orden->operario?->name ?? 'Sin asignar' }}</span>
                </div>
                @if($orden->fecha_inicio)
                    <div class="info-row">
                        <span class="info-label">Inicio</span>
                        <span class="info-value">{{ $orden->fecha_inicio->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($orden->tiempo_minutos)
                    <div class="info-row">
                        <span class="info-label">Tiempo</span>
                        <span class="info-value">{{ $orden->tiempoTranscurrido() }}</span>
                    </div>
                @endif
                @if($orden->observaciones)
                    <div style="margin-top:6px; padding-top:6px; border-top:1px solid #e2e8f0; color:#475569;">
                        {{ $orden->observaciones }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Notas del pedido --}}
    @if($pedido->notas)
        <div class="notas-box">
            <div class="titulo">⚠ Instrucciones especiales del pedido</div>
            <div class="texto">{{ $pedido->notas }}</div>
        </div>
    @endif

    {{-- Productos a producir --}}
    <table class="productos">
        <thead>
            <tr>
                <th>Producto a producir</th>
                <th class="c">Cantidad</th>
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
                <td class="c bold" style="font-size:14px;">{{ $d->cantidad }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        SIP — Sistema Integral de Pedidos &nbsp;|&nbsp; {{ $fecha }}
    </div>
</div>

</body>
</html>
