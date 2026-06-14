@extends('reportes.layout')

@section('header-extra')
    Período: {{ $periodo }}
@endsection

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor green">${{ number_format($resumen['ingresos'], 2) }}</div>
            <div class="label">Ingresos totales</div>
        </div>
        <div class="resumen-card">
            <div class="valor red">${{ number_format($resumen['costos'], 2) }}</div>
            <div class="label">Costos FIFO</div>
        </div>
        <div class="resumen-card">
            <div class="valor blue">${{ number_format($resumen['ganancia'], 2) }}</div>
            <div class="label">Ganancia neta</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $resumen['margen'] }}%</div>
            <div class="label">Margen promedio</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="r">Unidades</th>
                <th class="r">Precio prom.</th>
                <th class="r">Costo prom. FIFO</th>
                <th class="r">Ingresos</th>
                <th class="r">Costos</th>
                <th class="r">Ganancia</th>
                <th class="r">Margen %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
            @php $margen = (float)($f['margen_pct'] ?? 0); @endphp
            <tr>
                <td class="bold">{{ $f['producto'] }}</td>
                <td>{{ $f['categoria'] }}</td>
                <td class="r">{{ $f['unidades_vendidas'] }}</td>
                <td class="r">${{ number_format($f['precio_promedio_venta'], 2) }}</td>
                <td class="r">${{ number_format($f['costo_promedio_fifo'], 2) }}</td>
                <td class="r green">${{ number_format($f['ingresos'], 2) }}</td>
                <td class="r red">${{ number_format($f['costos'], 2) }}</td>
                <td class="r bold blue">${{ number_format($f['ganancia_bruta'], 2) }}</td>
                <td class="r {{ $margen >= 20 ? 'green' : ($margen >= 10 ? '' : 'red') }}">
                    {{ number_format($margen, 1) }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
