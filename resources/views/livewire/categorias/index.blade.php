<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Categorías de productos</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Gestión de categorías del catálogo</flux:text>
        </div>
        <flux:button wire:click="abrirCrear" icon="plus">Nueva categoría</flux:button>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar categoría…"
                    icon="magnifying-glass"
                    class="w-64" />
        <label class="flex items-center gap-2 text-sm">
            <flux:checkbox wire:model.live="soloActivas" />
            Solo activas
        </label>
    </div>

    {{-- Tabla --}}
    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Descripción</flux:table.column>
                <flux:table.column class="text-center">Productos</flux:table.column>
                <flux:table.column class="text-center">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($categorias as $cat)
                    <flux:table.row :key="$cat->id">
                        <flux:table.cell class="font-semibold">{{ $cat->nombre }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400 max-w-sm truncate">
                            {{ $cat->descripcion ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            @if($cat->productos_count > 0)
                                <flux:badge size="sm" color="blue">{{ $cat->productos_count }}</flux:badge>
                            @else
                                <span class="text-zinc-400">0</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            @if($cat->activo)
                                <flux:badge size="sm" color="lime">Activa</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Inactiva</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="abrirEditar({{ $cat->id }})" icon="pencil">
                                        Editar
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="toggleActivo({{ $cat->id }})"
                                        icon="{{ $cat->activo ? 'eye-slash' : 'eye' }}">
                                        {{ $cat->activo ? 'Desactivar' : 'Activar' }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="confirmarEliminar({{ $cat->id }})"
                                        icon="trash"
                                        variant="danger">
                                        Eliminar
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-8 text-center text-zinc-400">
                            No se encontraron categorías.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $categorias->links() }}

    {{-- Modal crear / editar --}}
    <flux:modal wire:model="modalAbierto" class="max-w-md space-y-6">
        <flux:heading>{{ $editandoId ? 'Editar categoría' : 'Nueva categoría' }}</flux:heading>

        <div class="space-y-4">
            <flux:field>
                <flux:label>Nombre *</flux:label>
                <flux:input wire:model="nombre" placeholder="Ej. Tazas, Camisetas…" autofocus />
                @error('nombre')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror
            </flux:field>

            <flux:field>
                <flux:label>Descripción</flux:label>
                <flux:textarea wire:model="descripcion" rows="3"
                               placeholder="Descripción opcional de la categoría…" />
                @error('descripcion')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror
            </flux:field>

            <flux:field>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <flux:checkbox wire:model="activo" />
                    Categoría activa
                </label>
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalAbierto', false)" variant="ghost">Cancelar</flux:button>
            <flux:button wire:click="guardar" variant="primary">Guardar</flux:button>
        </div>
    </flux:modal>

    {{-- Modal confirmar eliminación --}}
    <flux:modal wire:model="modalEliminar" class="max-w-sm space-y-4">
        <flux:heading>Eliminar categoría</flux:heading>
        <flux:text>
            ¿Confirmas que deseas eliminar la categoría
            <strong>{{ $eliminandoNombre }}</strong>?
            Esta acción no se puede deshacer.
        </flux:text>
        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalEliminar', false)" variant="ghost">Cancelar</flux:button>
            <flux:button wire:click="eliminar" variant="danger">Eliminar</flux:button>
        </div>
    </flux:modal>
</div>
