<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Solicitudes de reabastecimiento</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Solicitudes generadas por pedidos sin stock y por stock bajo el mínimo</flux:text>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <flux:select wire:model.live="filtroTipo" class="w-44">
            <flux:select.option value="">Todos los tipos</flux:select.option>
            <flux:select.option value="general">Stock bajo mínimo</flux:select.option>
            <flux:select.option value="pedido">Por pedido</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filtroEstado" class="w-44">
            <flux:select.option value="">Todos los estados</flux:select.option>
            <flux:select.option value="pendiente">Pendiente</flux:select.option>
            <flux:select.option value="en_proceso">En proceso</flux:select.option>
            <flux:select.option value="recibido">Recibido</flux:select.option>
            <flux:select.option value="cancelado">Cancelado</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filtroPrioridad" class="w-44">
            <flux:select.option value="">Todas las prioridades</flux:select.option>
            <flux:select.option value="1">Alta</flux:select.option>
            <flux:select.option value="2">Normal</flux:select.option>
            <flux:select.option value="3">Baja</flux:select.option>
        </flux:select>
    </div>

    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Producto</flux:table.column>
                <flux:table.column>Origen</flux:table.column>
                <flux:table.column class="text-center">Prioridad</flux:table.column>
                <flux:table.column class="text-center">Estado</flux:table.column>
                <flux:table.column class="text-center">Cant. sugerida</flux:table.column>
                <flux:table.column class="text-center">Stock disp.</flux:table.column>
                <flux:table.column>Solicitado el</flux:table.column>
                <flux:table.column>Atendido por</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($solicitudes as $s)
                    <flux:table.row :key="$s->id">
                        <flux:table.cell class="font-mono text-zinc-500 dark:text-zinc-400">{{ $s->id }}</flux:table.cell>

                        <flux:table.cell>
                            <div class="font-medium">{{ $s->producto->nombre }}</div>
                            <div class="text-xs text-zinc-400">{{ $s->producto->categoria->nombre }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($s->pedido_id)
                                <a href="{{ route('pedidos.ver', $s->pedido_id) }}" wire:navigate
                                   class="font-mono text-blue-600 hover:underline dark:text-blue-400">
                                    #{{ $s->pedido_id }}
                                </a>
                                <div class="text-xs text-zinc-400">{{ $s->pedido->cliente->nombre }}</div>
                            @else
                                <flux:badge size="sm" color="orange" icon="exclamation-triangle">
                                    Stock bajo mínimo
                                </flux:badge>
                                <div class="mt-1 text-xs text-zinc-400">
                                    Mín: {{ $s->producto->stock_minimo }} u.
                                </div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge :color="$s->prioridadColor()" size="sm">
                                {{ $s->prioridadLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge :color="$s->estadoColor()" size="sm">
                                {{ $s->estadoLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-center font-semibold">
                            {{ $s->cantidad_pedida }}
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            @php $disponible = $s->producto->stock_actual - $s->producto->stock_reservado; @endphp
                            <span class="{{ $disponible <= 0 ? 'text-red-600 font-semibold' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $disponible }}
                            </span>
                        </flux:table.cell>

                        <flux:table.cell class="text-sm text-zinc-400">
                            {{ $s->created_at->format('d/m/Y H:i') }}
                        </flux:table.cell>

                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $s->atendidoPor?->name ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if(in_array($s->estado, ['pendiente', 'en_proceso']))
                                <flux:dropdown>
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        @if($s->estado === 'pendiente')
                                            <flux:menu.item wire:click="marcarEnProceso({{ $s->id }})" icon="arrow-path">
                                                Marcar en proceso
                                            </flux:menu.item>
                                        @endif
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="cancelar({{ $s->id }})" icon="x-circle" variant="danger">
                                            Cancelar solicitud
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="10" class="py-8 text-center text-zinc-400">
                            No hay solicitudes con este filtro.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $solicitudes->links() }}
</div>
