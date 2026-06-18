<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Pedidos</flux:heading>
        <div class="flex gap-2">
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" variant="ghost" size="sm">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-arrow-down"
                        href="{{ route('reportes.pedidos', ['formato' => 'pdf', 'estado' => $filtroEstado, 'busqueda' => $busqueda, 'desde' => $desde, 'hasta' => $hasta]) }}"
                        target="_blank">
                        Descargar PDF
                    </flux:menu.item>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('reportes.pedidos', ['formato' => 'csv', 'estado' => $filtroEstado, 'busqueda' => $busqueda, 'desde' => $desde, 'hasta' => $hasta]) }}">
                        Descargar CSV
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button href="{{ route('pedidos.crear') }}" wire:navigate icon="plus">
                Nuevo pedido
            </flux:button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="space-y-3">
        <div class="flex flex-wrap gap-3">
            <flux:input wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por cliente…" icon="magnifying-glass" class="w-64" />

            <flux:select wire:model.live="filtroEstado" class="w-48">
                <flux:select.option value="">Todos los estados</flux:select.option>
                <flux:select.option value="esperando_stock">Esperando stock</flux:select.option>
                <flux:select.option value="pendiente">Pendiente</flux:select.option>
                <flux:select.option value="en_produccion">En producción</flux:select.option>
                <flux:select.option value="listo">Listo</flux:select.option>
                <flux:select.option value="entregado">Entregado</flux:select.option>
                <flux:select.option value="cancelado">Cancelado</flux:select.option>
            </flux:select>

            <div class="flex items-center gap-2">
                <flux:input type="date" wire:model="inputDesde" class="w-40" />
                <span class="text-sm text-zinc-400">—</span>
                <flux:input type="date" wire:model="inputHasta" class="w-40" />
                <flux:button wire:click="aplicarFechas" size="sm" variant="filled">Aplicar</flux:button>
                @if($desde || $hasta)
                    <flux:button wire:click="limpiarFechas" size="sm" variant="ghost" icon="x-mark" />
                @endif
            </div>
        </div>

        @if($desde || $hasta)
            <flux:callout icon="funnel" color="blue" inline>
                Mostrando pedidos del {{ $desde ? \Carbon\Carbon::parse($desde)->format('d/m/Y') : '…' }}
                al {{ $hasta ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : '…' }}.
                El PDF y CSV respetarán este rango.
            </flux:callout>
        @else
            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                Ajusta el rango de fechas y pulsa <strong>Aplicar</strong> para filtrar la tabla y los reportes exportados.
            </p>
        @endif
    </div>

    {{-- Tabla --}}
    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Cliente</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Fecha prometida</flux:table.column>
                <flux:table.column class="text-right">Total</flux:table.column>
                <flux:table.column class="text-right">Ganancia</flux:table.column>
                <flux:table.column>Registrado por</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($pedidos as $pedido)
                    <flux:table.row :key="$pedido->id">
                        <flux:table.cell>
                            <a href="{{ route('pedidos.ver', $pedido) }}" wire:navigate
                               class="font-mono text-blue-600 hover:underline dark:text-blue-400">
                                #{{ $pedido->id }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $pedido->cliente->nombre }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$pedido->estadoColor()" size="sm">
                                {{ $pedido->estadoLabel() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($pedido->fecha_prometida)
                                <span class="{{ $pedido->fecha_prometida->isPast() && !in_array($pedido->estado, ['entregado','cancelado']) ? 'text-red-600 font-semibold' : '' }}">
                                    {{ $pedido->fecha_prometida->format('d/m/Y') }}
                                </span>
                            @else
                                <flux:text class="text-zinc-400">—</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right">${{ number_format($pedido->total, 2) }}</flux:table.cell>
                        <flux:table.cell class="text-right text-lime-600">${{ number_format($pedido->ganancia, 2) }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">{{ $pedido->usuario->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item :href="route('pedidos.ver', $pedido)" wire:navigate icon="eye">
                                        Ver detalle
                                    </flux:menu.item>
                                    @if($pedido->puedeIrAProduccion())
                                        <flux:menu.item wire:click="enviarAProduccion({{ $pedido->id }})" icon="arrow-right-circle">
                                            Enviar a producción
                                        </flux:menu.item>
                                    @endif
                                    @if($pedido->puedeEntregarse())
                                        <flux:menu.item wire:click="marcarEntregado({{ $pedido->id }})" icon="check-circle">
                                            Marcar entregado
                                        </flux:menu.item>
                                    @endif
                                    @if($pedido->puedeCancelarse())
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="cancelar({{ $pedido->id }})" icon="x-circle" variant="danger">
                                            Cancelar pedido
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-8 text-center text-zinc-400">
                            No se encontraron pedidos.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $pedidos->links() }}
</div>
