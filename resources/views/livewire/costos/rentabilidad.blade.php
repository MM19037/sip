<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Costos — Rentabilidad</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Análisis de márgenes con costos FIFO reales</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('costos.lotes')" wire:navigate variant="ghost" icon="tag">Lotes</flux:button>
            <flux:button :href="route('costos.valoracion')" wire:navigate variant="ghost" icon="chart-bar">Valoración</flux:button>
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" variant="ghost" size="sm">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-arrow-down"
                        href="{{ route('reportes.costos.rentabilidad', ['formato' => 'pdf', 'anio' => $anio, 'mes' => $mes]) }}"
                        target="_blank">
                        Descargar PDF
                    </flux:menu.item>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('reportes.costos.rentabilidad', ['formato' => 'csv', 'anio' => $anio, 'mes' => $mes]) }}">
                        Descargar CSV
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Filtros de período --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:select wire:model.live="anio" class="w-28">
            @foreach($aniosDisponibles as $a)
                <flux:select.option value="{{ $a }}">{{ $a }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="mes" class="w-36">
            <flux:select.option value="">Todo el año</flux:select.option>
            @foreach(range(1,12) as $m)
                <flux:select.option value="{{ $m }}">
                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Tarjetas resumen del período --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Ingresos</flux:text>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                ${{ number_format($resumen['ingresos'], 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Costos FIFO</flux:text>
            <p class="mt-1 text-2xl font-bold text-red-500">
                ${{ number_format($resumen['costos'], 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Ganancia bruta</flux:text>
            <p class="mt-1 text-2xl font-bold {{ $resumen['ganancia'] >= 0 ? 'text-lime-600' : 'text-red-600' }}">
                ${{ number_format($resumen['ganancia'], 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Margen bruto</flux:text>
            <p class="mt-1 text-2xl font-bold {{ $resumen['margen_pct'] >= 0 ? 'text-lime-600' : 'text-red-600' }}">
                {{ number_format($resumen['margen_pct'], 1) }}%
            </p>
        </flux:card>
    </div>

    @if($porProducto->isEmpty())
        <flux:card>
            <p class="py-8 text-center text-zinc-400">
                No hay pedidos entregados en el período seleccionado.
            </p>
        </flux:card>
    @else
        {{-- Rentabilidad por categoría --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Por categoría</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column class="text-right">Ingresos</flux:table.column>
                    <flux:table.column class="text-right">Costos</flux:table.column>
                    <flux:table.column class="text-right">Ganancia</flux:table.column>
                    <flux:table.column class="text-right">Margen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($porCategoria as $cat)
                        <flux:table.row>
                            <flux:table.cell class="font-medium">{{ $cat['categoria'] }}</flux:table.cell>
                            <flux:table.cell class="text-right font-mono">
                                ${{ number_format($cat['ingresos'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono text-red-500">
                                ${{ number_format($cat['costos'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono font-semibold
                                {{ $cat['ganancia'] >= 0 ? 'text-lime-600' : 'text-red-600' }}">
                                ${{ number_format($cat['ganancia'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:badge size="sm"
                                    :color="$cat['margen_pct'] >= 30 ? 'lime' : ($cat['margen_pct'] >= 10 ? 'yellow' : 'red')">
                                    {{ number_format($cat['margen_pct'], 1) }}%
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Rentabilidad por producto --}}
        <flux:card class="p-0">
            <div class="px-4 py-3">
                <flux:heading size="lg">Detalle por producto</flux:heading>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column class="text-center">Unidades</flux:table.column>
                    <flux:table.column class="text-right">Precio prom.</flux:table.column>
                    <flux:table.column class="text-right">Costo FIFO prom.</flux:table.column>
                    <flux:table.column class="text-right">Ingresos</flux:table.column>
                    <flux:table.column class="text-right">Costos</flux:table.column>
                    <flux:table.column class="text-right">Ganancia</flux:table.column>
                    <flux:table.column class="text-right">Margen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($porProducto as $prod)
                        <flux:table.row>
                            <flux:table.cell class="font-medium">{{ $prod['producto'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $prod['categoria'] }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-center font-mono">
                                {{ number_format($prod['unidades_vendidas']) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono">
                                ${{ number_format($prod['precio_prom'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono text-red-500">
                                ${{ number_format($prod['costo_prom_fifo'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono">
                                ${{ number_format($prod['ingresos'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono text-red-500">
                                ${{ number_format($prod['costos'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono font-semibold
                                {{ $prod['ganancia'] >= 0 ? 'text-lime-600' : 'text-red-600' }}">
                                ${{ number_format($prod['ganancia'], 2) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:badge size="sm"
                                    :color="$prod['margen_pct'] >= 30 ? 'lime' : ($prod['margen_pct'] >= 10 ? 'yellow' : 'red')">
                                    {{ number_format($prod['margen_pct'], 1) }}%
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
