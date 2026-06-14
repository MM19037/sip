<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Usuarios del sistema</flux:heading>
        <flux:button wire:click="abrirCrear" icon="plus">Nuevo usuario</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por nombre o email…"
                icon="magnifying-glass" class="w-72" />

    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Rol</flux:table.column>
                <flux:table.column class="text-center">Estado</flux:table.column>
                <flux:table.column>Creado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($usuarios as $u)
                    <flux:table.row :key="$u->id">
                        <flux:table.cell class="font-medium">
                            {{ $u->name }}
                            @if($u->id === auth()->id())
                                <flux:badge color="blue" size="sm" class="ml-1">Tú</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">{{ $u->email }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $rolColor = match($u->rol) {
                                    'administrador' => 'red', 'recepcionista' => 'blue', 'produccion' => 'yellow', default => 'zinc'
                                };
                                $rolLabel = match($u->rol) {
                                    'administrador' => 'Administrador', 'recepcionista' => 'Recepcionista', 'produccion' => 'Producción', default => $u->rol
                                };
                            @endphp
                            <flux:badge :color="$rolColor" size="sm">{{ $rolLabel }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            <flux:badge :color="$u->activo ? 'lime' : 'zinc'" size="sm">
                                {{ $u->activo ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-400">
                            {{ $u->created_at->format('d/m/Y') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="abrirEditar({{ $u->id }})" icon="pencil">Editar</flux:menu.item>
                                    @if($u->id !== auth()->id())
                                        <flux:menu.item wire:click="toggleActivo({{ $u->id }})"
                                                        icon="{{ $u->activo ? 'eye-slash' : 'eye' }}">
                                            {{ $u->activo ? 'Desactivar' : 'Activar' }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center text-zinc-400">
                            No se encontraron usuarios.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $usuarios->links() }}

    {{-- Modal crear/editar usuario --}}
    <flux:modal wire:model="modalAbierto" class="max-w-md space-y-6">
        <flux:heading>{{ $editandoId ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>

        <div class="space-y-4">
            <flux:field>
                <flux:label>Nombre *</flux:label>
                <flux:input wire:model="name" />
                @error('name') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Email *</flux:label>
                <flux:input wire:model="email" type="email" />
                @error('email') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Rol *</flux:label>
                <flux:select wire:model="rol">
                    <flux:select.option value="administrador">Administrador</flux:select.option>
                    <flux:select.option value="recepcionista">Recepcionista</flux:select.option>
                    <flux:select.option value="produccion">Producción</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ $editandoId ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' }}</flux:label>
                <flux:input wire:model="password" type="password" autocomplete="new-password" viewable />
                @error('password') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Confirmar contraseña</flux:label>
                <flux:input wire:model="passwordConfirmation" type="password" autocomplete="new-password" viewable />
                @error('passwordConfirmation') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <flux:checkbox wire:model="activo" />
                    Usuario activo
                </label>
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalAbierto', false)" variant="ghost">Cancelar</flux:button>
            <flux:button wire:click="guardar" variant="primary">Guardar</flux:button>
        </div>
    </flux:modal>
</div>
