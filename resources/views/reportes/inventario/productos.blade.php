@extends('reportes.layout')

@section('header-extra')
    Total: {{ $productos->count() }} productos
@endsection

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor">{{ $productos->count() }}</div>
            <div class="label">Productos totales</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $productos->where('activo', true)->count() }}</div>
            <div class="label">Activos</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $productos->sum('stock_actual') }}</div>
            <div class="label">Unidades en stock</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $productos->sum('disponible') }}</div>
            <div class="label">Unidades disponibles</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="r">Costo base</th>
                <th class="r">Precio venta</th>
                <th class="r">Stock actual</th>
                <th class="r">Reservado</th>
                <th class="r">Disponible</th>
                <th class="r">Mínimo</th>
                <th class="c">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $p)
            @php $sinStock = $p['disponible'] <= 0; $bajo = !$sinStock && $p['disponible'] <= $p['minimo']; @endphp
            <tr>
                <td class="bold">{{ $p['nombre'] }}</td>
                <td>{{ $p['categoria'] }}</td>
                <td class="r">${{ number_format($p['costo_base'], 2) }}</td>
                <td class="r blue">${{ number_format($p['precio_venta'], 2) }}</td>
                <td class="r">{{ $p['stock_actual'] }}</td>
                <td class="r">{{ $p['reservado'] }}</td>
                <td class="r {{ $sinStock ? 'red bold' : ($bajo ? 'red' : 'green') }}">{{ $p['disponible'] }}</td>
                <td class="r">{{ $p['minimo'] }}</td>
                <td class="c">{{ $p['activo'] ? 'Activo' : 'Inactivo' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
