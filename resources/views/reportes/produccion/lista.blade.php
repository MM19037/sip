@extends('reportes.layout')

@section('header-extra')
    {{ count($filtros) ? implode(' | ', array_map(fn($k,$v) => "$k: $v", array_keys($filtros), $filtros)) : 'Todas las órdenes activas' }}
@endsection

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor">{{ $resumen['total'] }}</div>
            <div class="label">Total órdenes</div>
        </div>
        <div class="resumen-card" style="border-left-color: #7c3aed;">
            <div class="valor" style="color: #7c3aed;">{{ $resumen['asignadas'] }}</div>
            <div class="label">Asignadas</div>
        </div>
        <div class="resumen-card" style="border-left-color: #2563eb;">
            <div class="valor" style="color: #2563eb;">{{ $resumen['en_proceso'] }}</div>
            <div class="label">En proceso</div>
        </div>
        <div class="resumen-card" style="border-left-color: #d97706;">
            <div class="valor" style="color: #d97706;">{{ $resumen['pausadas'] }}</div>
            <div class="label">Pausadas</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>#OP</th>
                <th>Pedido</th>
                <th>Cliente</th>
                <th class="c">Estado</th>
                <th class="c">Prioridad</th>
                <th>Operario</th>
                <th class="c">Inicio</th>
                <th class="c">Tiempo</th>
                <th class="c">Entrega prom.</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordenes as $o)
            @php
                $estadoStyle = match($o['estado']) {
                    'En proceso' => 'color:#1d4ed8; font-weight:700;',
                    'Asignado'   => 'color:#6d28d9; font-weight:700;',
                    'Pausado'    => 'color:#b45309; font-weight:700;',
                    'Completado' => 'color:#15803d; font-weight:700;',
                    default      => '',
                };
                $prioStyle = match($o['prioridad']) {
                    'Alta'  => 'color:#dc2626; font-weight:700;',
                    'Baja'  => 'color:#64748b;',
                    default => '',
                };
            @endphp
            <tr>
                <td class="bold blue">#OP{{ $o['id'] }}</td>
                <td class="c">#{{ $o['pedido_id'] }}</td>
                <td class="bold">{{ $o['cliente'] }}</td>
                <td class="c" style="{{ $estadoStyle }}">{{ $o['estado'] }}</td>
                <td class="c" style="{{ $prioStyle }}">{{ $o['prioridad'] }}</td>
                <td>{{ $o['operario'] }}</td>
                <td class="c">{{ $o['fecha_inicio'] }}</td>
                <td class="c">{{ $o['tiempo'] }}</td>
                <td class="c">{{ $o['fecha_prometida'] }}</td>
                <td style="font-size:8px; color:#64748b;">{{ $o['observaciones'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
