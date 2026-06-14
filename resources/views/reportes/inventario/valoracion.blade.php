@extends('reportes.layout')

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor">${{ number_format($resumen['valor_total'], 2) }}</div>
            <div class="label">Valor total FIFO</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $resumen['lotes_activos'] }}</div>
            <div class="label">Lotes activos</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $resumen['productos'] }}</div>
            <div class="label">Productos</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Producto</th>
                <th class="r">Stock actual</th>
                <th class="r">Reservado</th>
                <th class="r">Libre</th>
                <th class="r">Lotes activos</th>
                <th class="r">Costo prom. FIFO</th>
                <th class="r">Valor FIFO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
            <tr>
                <td>{{ $f['categoria'] }}</td>
                <td class="bold">{{ $f['producto'] }}</td>
                <td class="r">{{ $f['stock_actual'] }}</td>
                <td class="r">{{ $f['stock_reservado'] }}</td>
                <td class="r green">{{ $f['stock_libre'] }}</td>
                <td class="r c">{{ $f['lotes_activos'] }}</td>
                <td class="r">${{ number_format($f['costo_promedio_fifo'], 2) }}</td>
                <td class="r bold blue">${{ number_format($f['valor_total_fifo'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
