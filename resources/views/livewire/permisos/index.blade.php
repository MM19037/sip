<div class="space-y-6">
    <div>
        <flux:heading size="xl">Permisos por rol</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-400">
            Activa o desactiva el acceso a cada sección según el rol. El administrador siempre tiene acceso completo.
        </flux:text>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Sección</flux:table.column>
                <flux:table.column class="text-center">Administrador</flux:table.column>
                @foreach ($roles as $rol)
                    <flux:table.column class="text-center capitalize">{{ $rol }}</flux:table.column>
                @endforeach
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($grupos as $grupo => $seccionesDelGrupo)
                    {{-- Encabezado de módulo --}}
                    <flux:table.row>
                        <flux:table.cell
                            colspan="{{ 2 + count($roles) }}"
                            class="bg-zinc-100 dark:bg-zinc-700/50 py-2 px-4"
                        >
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ $grupo }}
                            </span>
                        </flux:table.cell>
                    </flux:table.row>

                    @foreach ($seccionesDelGrupo as $seccion)
                        <flux:table.row>
                            <flux:table.cell class="pl-6 font-medium">
                                {{ $secciones[$seccion] ?? $seccion }}
                            </flux:table.cell>

                            {{-- Administrador: siempre activo --}}
                            <flux:table.cell class="text-center">
                                <flux:badge color="green" size="sm">Siempre</flux:badge>
                            </flux:table.cell>

                            {{-- Roles configurables --}}
                            @foreach ($roles as $rol)
                                <flux:table.cell class="text-center">
                                    <button
                                        wire:click="toggle('{{ $seccion }}', '{{ $rol }}')"
                                        wire:loading.attr="disabled"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                            {{ ($permisos[$seccion][$rol] ?? true) ? 'bg-blue-600' : 'bg-zinc-600' }}"
                                    >
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                            {{ ($permisos[$seccion][$rol] ?? true) ? 'translate-x-6' : 'translate-x-1' }}">
                                        </span>
                                    </button>
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @endforeach
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:text class="text-xs text-zinc-500">
        Los cambios se aplican de inmediato. Los permisos se cachean por 10 minutos por rol.
    </flux:text>
</div>
