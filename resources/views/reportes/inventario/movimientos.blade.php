@extends('reportes.layout')

@section('header-extra')
    Período: {{ $rango }}
@endsection

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor">{{ $movimientos->count() }}</div>
            <div class="label">Movimientos totales</div>
        </div>
        <div class="resumen-card">
            <div class="valor green">{{ $movimientos->where('tipo', 'Entrada')->sum('cantidad') }}</div>
            <div class="label">Unidades entradas</div>
        </div>
        <div class="resumen-card">
            <div class="valor red">{{ $movimientos->where('tipo', 'Salida')->sum('cantidad') }}</div>
            <div class="label">Unidades salidas</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $movimientos->where('tipo', 'Ajuste')->count() }}</div>
            <div class="label">Ajustes</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th class="c">Tipo</th>
                <th class="r">Cantidad</th>
                <th class="r">Costo unit.</th>
                <th>Motivo</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $m)
            <tr>
                <td>{{ $m['fecha'] }}</td>
                <td class="bold">{{ $m['producto'] }}</td>
                <td class="c {{ $m['tipo'] === 'Entrada' ? 'green' : ($m['tipo'] === 'Salida' ? 'red' : '') }}">
                    {{ $m['tipo'] }}
                </td>
                <td class="r">{{ $m['cantidad'] }}</td>
                <td class="r">${{ number_format($m['costo'], 2) }}</td>
                <td>{{ $m['motivo'] }}</td>
                <td>{{ $m['usuario'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
