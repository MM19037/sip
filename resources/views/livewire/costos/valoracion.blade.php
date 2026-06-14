<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Costos — Valoración de inventario</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Valor actual del inventario calculado con costeo FIFO</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('costos.lotes')" wire:navigate variant="ghost" icon="tag">Lotes</flux:button>
            <flux:button :href="route('costos.rentabilidad')" wire:navigate variant="ghost" icon="presentation-chart-line">Rentabilidad</flux:button>
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" variant="ghost" size="sm">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-arrow-down"
                        href="{{ route('reportes.inventario.valoracion', ['formato' => 'pdf']) }}"
                        target="_blank">
                        Descargar PDF
                    </flux:menu.item>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('reportes.inventario.valoracion', ['formato' => 'csv']) }}">
                        Descargar CSV
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Resumen global --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <flux:card class="col-span-2">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Valor total inventario (FIFO)</flux:text>
            <p class="mt-1 text-3xl font-bold text-lime-600">
                ${{ number_format($resumen['valor_total'], 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Valor libre</flux:text>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                ${{ number_format($resumen['valor_libre'], 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Valor reservado</flux:text>
            <p class="mt-1 text-2xl font-bold text-blue-600">
                ${{ number_format($resumen['valor_reservado'], 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Lotes activos</flux:text>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ number_format($resumen['lotes_activos']) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Productos con stock</flux:text>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ $resumen['productos_con_stock'] }}
                @if($resumen['productos_sin_stock'] > 0)
                    <span class="text-base font-normal text-red-500">
                        ({{ $resumen['productos_sin_stock'] }} sin stock)
                    </span>
                @endif
            </p>
        </flux:card>
    </div>

    {{-- Resumen por categoría --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Valor por categoría</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column class="text-center">Productos</flux:table.column>
                <flux:table.column class="text-center">Lotes activos</flux:table.column>
                <flux:table.column class="text-center">Unidades</flux:table.column>
                <flux:table.column class="text-right">Valor FIFO</flux:table.column>
                <flux:table.column class="text-right">% del total</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($porCategoria as $cat)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $cat['categoria'] }}</flux:table.cell>
                        <flux:table.cell class="text-center">{{ $cat['productos'] }}</flux:table.cell>
                        <flux:table.cell class="text-center">{{ $cat['lotes_activos'] }}</flux:table.cell>
                        <flux:table.cell class="text-center font-mono">{{ number_format($cat['unidades_en_lotes']) }}</flux:table.cell>
                        <flux:table.cell class="text-right font-mono font-semibold">
                            ${{ number_format($cat['valor_total_fifo'], 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right text-sm text-zinc-500 dark:text-zinc-400">
                            @if($resumen['valor_total'] > 0)
                                {{ number_format($cat['valor_total_fifo'] / $resumen['valor_total'] * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Detalle por producto --}}
    <flux:card class="p-0">
        <div class="px-4 py-3">
            <flux:heading size="lg">Detalle por producto</flux:heading>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Producto</flux:table.column>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column class="text-center">Stock total</flux:table.column>
                <flux:table.column class="text-center">Reservado</flux:table.column>
                <flux:table.column class="text-center">Libre</flux:table.column>
                <flux:table.column class="text-center">En lotes</flux:table.column>
                <flux:table.column class="text-right">Costo prom. FIFO</flux:table.column>
                <flux:table.column class="text-right">Valor FIFO</flux:table.column>
                <flux:table.column class="text-center">Lotes</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($porProducto as $p)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $p->producto }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $p->categoria }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono">{{ $p->stock_actual }}</flux:table.cell>
                        <flux:table.cell class="text-center font-mono text-blue-600">{{ $p->stock_reservado }}</flux:table.cell>
                        <flux:table.cell class="text-center font-mono
                            {{ $p->stock_libre > 0 ? 'text-lime-600 font-semibold' : 'text-red-500' }}">
                            {{ $p->stock_libre }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono">{{ $p->unidades_en_lotes }}</flux:table.cell>
                        <flux:table.cell class="text-right font-mono">
                            ${{ number_format($p->costo_promedio_fifo, 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right font-mono font-semibold">
                            @if($p->valor_total_fifo > 0)
                                ${{ number_format($p->valor_total_fifo, 2) }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            @if($p->lotes_activos > 0)
                                <flux:badge size="sm" color="lime">{{ $p->lotes_activos }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="red">Sin lotes</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
