<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Costos — Lotes de inventario</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Trazabilidad FIFO de cada entrada de stock</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('costos.valoracion')" wire:navigate variant="ghost" icon="chart-bar">Valoración</flux:button>
            <flux:button :href="route('costos.rentabilidad')" wire:navigate variant="ghost" icon="presentation-chart-line">Rentabilidad</flux:button>
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" variant="ghost" size="sm">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-arrow-down"
                        href="{{ route('reportes.costos.lotes', ['formato' => 'pdf']) }}"
                        target="_blank">
                        Descargar PDF
                    </flux:menu.item>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('reportes.costos.lotes', ['formato' => 'csv']) }}">
                        Descargar CSV
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Lotes activos</flux:text>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ number_format($resumen->total_lotes ?? 0) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Unidades en stock</flux:text>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ number_format($resumen->unidades_total ?? 0) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Valor total FIFO</flux:text>
            <p class="mt-1 text-2xl font-bold text-lime-600">
                ${{ number_format($resumen->valor_total ?? 0, 2) }}
            </p>
        </flux:card>
        <flux:card>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Valor reservado</flux:text>
            <p class="mt-1 text-2xl font-bold text-blue-600">
                ${{ number_format($resumen->valor_reservado ?? 0, 2) }}
            </p>
        </flux:card>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3">
        <flux:input wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar producto…"
                    icon="magnifying-glass"
                    class="w-56" />

        <flux:select wire:model.live="filtroCategoria" class="w-44">
            <flux:select.option value="">Todas las categorías</flux:select.option>
            @foreach($categorias as $cat)
                <flux:select.option value="{{ $cat->id }}">{{ $cat->nombre }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filtroEstado" class="w-40">
            <flux:select.option value="activos">Con stock</flux:select.option>
            <flux:select.option value="agotados">Agotados</flux:select.option>
            <flux:select.option value="todos">Todos</flux:select.option>
        </flux:select>
    </div>

    {{-- Tabla de lotes --}}
    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>N° Lote</flux:table.column>
                <flux:table.column>Producto</flux:table.column>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column class="text-center">Fecha entrada</flux:table.column>
                <flux:table.column class="text-center">Inicial</flux:table.column>
                <flux:table.column class="text-center">Disponible</flux:table.column>
                <flux:table.column class="text-center">Reservado</flux:table.column>
                <flux:table.column class="text-center">Libre</flux:table.column>
                <flux:table.column class="text-right">Costo unit.</flux:table.column>
                <flux:table.column class="text-right">Valor disp.</flux:table.column>
                <flux:table.column class="text-center">Estado</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($lotes as $lote)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-sm font-medium">
                            {{ $lote->numero_lote }}
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">
                            {{ $lote->producto->nombre }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $lote->producto->categoria->nombre }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $lote->fecha_entrada->format('d/m/Y') }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono">
                            {{ $lote->cantidad_inicial }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono">
                            {{ $lote->cantidad_disponible }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono text-blue-600">
                            {{ $lote->cantidad_reservada }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono font-semibold
                            {{ $lote->cantidadLibre() > 0 ? 'text-lime-600' : 'text-zinc-400' }}">
                            {{ $lote->cantidadLibre() }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right font-mono">
                            ${{ number_format($lote->costo_unitario, 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right font-mono font-semibold">
                            ${{ number_format($lote->valorDisponible(), 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            @if($lote->estaAgotado())
                                <flux:badge size="sm" color="zinc">Agotado</flux:badge>
                            @elseif($lote->cantidadLibre() === 0)
                                <flux:badge size="sm" color="blue">Reservado</flux:badge>
                            @else
                                <flux:badge size="sm" color="lime">Disponible</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="11" class="py-8 text-center text-zinc-400">
                            No hay lotes que coincidan con los filtros.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $lotes->links() }}
</div>
