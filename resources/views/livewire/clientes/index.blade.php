<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Clientes</flux:heading>
        <flux:button wire:click="abrirCrear" icon="plus">Nuevo cliente</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por nombre, teléfono o email…"
                icon="magnifying-glass" class="w-80" />

    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Teléfono</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column class="text-center">Pedidos</flux:table.column>
                <flux:table.column>Registrado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($clientes as $c)
                    <flux:table.row :key="$c->id">
                        <flux:table.cell class="font-medium">{{ $c->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $c->telefono ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">{{ $c->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-center">
                            <flux:badge color="zinc" size="sm">{{ $c->pedidos_count }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-400">
                            {{ $c->created_at->format('d/m/Y') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="abrirEditar({{ $c->id }})" icon="pencil">Editar</flux:menu.item>
                                    <flux:menu.item wire:click="verHistorial({{ $c->id }})" icon="clock">Historial</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center text-zinc-400">
                            No se encontraron clientes.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $clientes->links() }}

    {{-- Modal crear/editar cliente --}}
    <flux:modal wire:model="modalAbierto" class="max-w-md space-y-6">
        <flux:heading>{{ $editandoId ? 'Editar cliente' : 'Nuevo cliente' }}</flux:heading>

        <div class="space-y-4">
            <flux:field>
                <flux:label>Nombre *</flux:label>
                <flux:input wire:model="nombre" />
                @error('nombre') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Teléfono</flux:label>
                <flux:input wire:model="telefono" type="tel" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="email" type="email" />
                @error('email') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Dirección</flux:label>
                <flux:textarea wire:model="direccion" rows="2" />
            </flux:field>

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notas" rows="2" placeholder="Preferencias, indicaciones especiales…" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalAbierto', false)" variant="ghost">Cancelar</flux:button>
            <flux:button wire:click="guardar" variant="primary">Guardar</flux:button>
        </div>
    </flux:modal>

    {{-- Modal historial --}}
    @if($historial)
        <flux:modal wire:model="verHistorialId" class="max-w-lg space-y-4">
            <flux:heading>Historial — {{ $historial->nombre }}</flux:heading>

            @if($historial->pedidos->isEmpty())
                <flux:text class="py-4 text-center text-zinc-400">Este cliente no tiene pedidos.</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>#</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Fecha</flux:table.column>
                        <flux:table.column class="text-right">Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($historial->pedidos as $p)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('pedidos.ver', $p) }}" wire:navigate
                                       class="font-mono text-blue-600 hover:underline">#{{ $p->id }}</a>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$p->estadoColor()" size="sm">{{ $p->estadoLabel() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-sm">{{ $p->fecha_pedido->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="text-right font-mono">${{ number_format($p->total, 2) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="$set('verHistorialId', null)" variant="ghost">Cerrar</flux:button>
            </div>
        </flux:modal>
    @endif
</div>
