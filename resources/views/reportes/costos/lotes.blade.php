@extends('reportes.layout')

@section('resumen')
    <div class="resumen">
        <div class="resumen-card">
            <div class="valor">{{ $resumen['total_lotes'] }}</div>
            <div class="label">Lotes activos</div>
        </div>
        <div class="resumen-card">
            <div class="valor">{{ $resumen['unidades'] }}</div>
            <div class="label">Unidades disponibles</div>
        </div>
        <div class="resumen-card">
            <div class="valor">${{ number_format($resumen['valor_total'], 2) }}</div>
            <div class="label">Valor total FIFO</div>
        </div>
    </div>
@endsection

@section('contenido')
    <table>
        <thead>
            <tr>
                <th>N° Lote</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="c">Fecha entrada</th>
                <th class="r">Inicial</th>
                <th class="r">Disponible</th>
                <th class="r">Reservado</th>
                <th class="r">Libre</th>
                <th class="r">Costo unit.</th>
                <th class="r">Valor disp.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lotes as $l)
            <tr>
                <td class="bold blue">{{ $l['numero_lote'] }}</td>
                <td class="bold">{{ $l['producto'] }}</td>
                <td>{{ $l['categoria'] }}</td>
                <td class="c">{{ $l['fecha_entrada'] }}</td>
                <td class="r">{{ $l['inicial'] }}</td>
                <td class="r green">{{ $l['disponible'] }}</td>
                <td class="r">{{ $l['reservado'] }}</td>
                <td class="r">{{ $l['libre'] }}</td>
                <td class="r">${{ number_format($l['costo_unitario'], 2) }}</td>
                <td class="r bold">${{ number_format($l['valor_disp'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
