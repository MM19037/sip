@extends('reportes.layout')

@section('header-extra')
    {{ count($filtros) ? implode(' | ', array_map(fn($k,$v) => "$k: $v", array_keys($filtros), $filtros)) : 'Todos los pedidos' }}
@endsection

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor">{{ $resumen['total_pedidos'] }}</div>
            <div class="label">Total pedidos</div>
        </div>
        <div class="resumen-card">
            <div class="valor">${{ number_format($resumen['ingresos'], 2) }}</div>
            <div class="label">Ingresos totales</div>
        </div>
        <div class="resumen-card">
            <div class="valor">${{ number_format($resumen['ganancia'], 2) }}</div>
            <div class="label">Ganancia total</div>
        </div>
        <div class="resumen-card">
            <div class="valor">
                {{ $resumen['ingresos'] > 0 ? number_format($resumen['ganancia'] / $resumen['ingresos'] * 100, 1) : 0 }}%
            </div>
            <div class="label">Margen promedio</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Fecha pedido</th>
                <th>Fecha prometida</th>
                <th class="r">Total</th>
                <th class="r">Ganancia</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedidos as $p)
            <tr>
                <td class="bold blue">#{{ $p['id'] }}</td>
                <td class="bold">{{ $p['cliente'] }}</td>
                <td>{{ $p['estado'] }}</td>
                <td>{{ $p['fecha_pedido'] }}</td>
                <td>{{ $p['fecha_prometida'] }}</td>
                <td class="r bold">${{ number_format($p['total'], 2) }}</td>
                <td class="r green">${{ number_format($p['ganancia'], 2) }}</td>
                <td>{{ $p['usuario'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
