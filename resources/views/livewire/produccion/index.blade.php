<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Producción</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Gestión de órdenes y operarios</flux:text>
        </div>
    </div>

    {{-- Panel de operarios ------------------------------------------------- --}}
    @if($operarios->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($operarios as $op)
                @php
                    $enProceso = $op->ordenesProduccion->firstWhere('estado', 'en_proceso');
                    $pausado   = $op->ordenesProduccion->firstWhere('estado', 'pausado');
                    $enCola    = $op->ordenesProduccion->where('estado', 'asignado')->count();

                    [$panelColor, $panelLabel] = match(true) {
                        (bool) $enProceso => ['blue',   'En proceso'],
                        (bool) $pausado   => ['orange', 'Pausado'],
                        default           => ['lime',   'Libre'],
                    };
                @endphp
                <flux:card class="flex items-start gap-3 p-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full
                                bg-zinc-100 text-sm font-bold text-zinc-600
                                dark:bg-zinc-700 dark:text-zinc-300">
                        {{ $op->initials() }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $op->name }}
                        </p>
                        <flux:badge :color="$panelColor" size="sm" class="mt-0.5">
                            {{ $panelLabel }}
                        </flux:badge>
                        @if($enProceso)
                            <p class="mt-1 text-xs text-zinc-400">
                                #OP{{ $enProceso->id }} · {{ $enProceso->tiempoTranscurrido() }}
                            </p>
                        @elseif($pausado)
                            <p class="mt-1 text-xs text-zinc-400">#OP{{ $pausado->id }}</p>
                        @endif
                        @if($enCola > 0)
                            <p class="mt-0.5 text-xs text-zinc-400">{{ $enCola }} en cola</p>
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    {{-- Filtros ------------------------------------------------------------ --}}
    <div class="flex flex-wrap gap-3">
        <flux:select wire:model.live="filtroEstado" class="w-48">
            <flux:select.option value="">Todos los estados</flux:select.option>
            <flux:select.option value="asignado">Asignado</flux:select.option>
            <flux:select.option value="en_proceso">En proceso</flux:select.option>
            <flux:select.option value="pausado">Pausado</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="filtroPrioridad" class="w-40">
            <flux:select.option value="">Toda prioridad</flux:select.option>
            <flux:select.option value="1">Alta</flux:select.option>
            <flux:select.option value="2">Normal</flux:select.option>
            <flux:select.option value="3">Baja</flux:select.option>
        </flux:select>
    </div>

    {{-- Tabla de órdenes -------------------------------------------------- --}}
    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Orden</flux:table.column>
                <flux:table.column>Pedido</flux:table.column>
                <flux:table.column>Cliente</flux:table.column>
                <flux:table.column class="text-center">Prioridad</flux:table.column>
                <flux:table.column class="text-center">Estado</flux:table.column>
                <flux:table.column class="text-center">Tiempo</flux:table.column>
                <flux:table.column>Operario</flux:table.column>
                <flux:table.column class="text-center">Entrega</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($ordenes as $orden)
                    <flux:table.row :key="$orden->id">
                        <flux:table.cell class="font-mono font-medium text-zinc-500 dark:text-zinc-400">
                            #OP{{ $orden->id }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <a href="{{ route('pedidos.ver', $orden->pedido_id) }}" wire:navigate
                               class="font-mono text-blue-600 hover:underline dark:text-blue-400">
                                #{{ $orden->pedido_id }}
                            </a>
                        </flux:table.cell>

                        <flux:table.cell class="font-medium">
                            {{ $orden->pedido->cliente->nombre }}
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge :color="$orden->prioridadColor()" size="sm">
                                {{ $orden->prioridadLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge :color="$orden->estadoColor()" size="sm">
                                {{ $orden->estadoLabel() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Tiempo transcurrido ------------------------------------------------ --}}
                        <flux:table.cell class="text-center">
                            @if($orden->fecha_inicio)
                                <flux:badge :color="$orden->tiempoSemaforo()" size="sm">
                                    {{ $orden->tiempoTranscurrido() }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Operario ----------------------------------------------------------- --}}
                        <flux:table.cell class="text-sm">
                            @if($orden->operario)
                                <div class="flex items-center gap-1.5">
                                    <div class="flex size-6 shrink-0 items-center justify-center rounded-full
                                                bg-zinc-100 text-xs font-bold text-zinc-600
                                                dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ $orden->operario->initials() }}
                                    </div>
                                    <span>{{ $orden->operario->name }}</span>
                                </div>
                            @else
                                <span class="text-zinc-400 italic">Sin asignar</span>
                            @endif
                        </flux:table.cell>

                        {{-- Fecha prometida ---------------------------------------------------- --}}
                        <flux:table.cell class="text-center text-sm">
                            @if($orden->pedido->fecha_prometida)
                                <span class="{{ $orden->pedido->fecha_prometida->isPast() ? 'font-semibold text-red-600' : 'text-zinc-600 dark:text-zinc-300' }}">
                                    {{ $orden->pedido->fecha_prometida->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Acciones ----------------------------------------------------------- --}}
                        <flux:table.cell>
                            <div class="flex gap-1">
                                @if($orden->estado === \App\Models\OrdenProduccion::ASIGNADO)
                                    @php
                                        $sinOperario     = ! $orden->usuario_id;
                                        $operarioOcupado = ! $sinOperario && \App\Models\OrdenProduccion::where('usuario_id', $orden->usuario_id)
                                            ->where('estado', 'en_proceso')
                                            ->where('id', '!=', $orden->id)
                                            ->exists();
                                        $bloqueado = $sinOperario || $operarioOcupado;
                                    @endphp
                                    <flux:button wire:click="iniciar({{ $orden->id }})"
                                                 size="sm"
                                                 variant="{{ $bloqueado ? 'danger' : 'ghost' }}"
                                                 icon="{{ $bloqueado ? 'no-symbol' : 'play' }}"
                                                 title="{{ $sinOperario ? 'Sin operario asignado' : ($operarioOcupado ? 'Operario ocupado en otra orden' : 'Iniciar producción') }}">
                                        Iniciar
                                    </flux:button>
                                @elseif($orden->estado === \App\Models\OrdenProduccion::EN_PROCESO)
                                    <flux:button wire:click="completar({{ $orden->id }})"
                                                 size="sm" variant="primary" icon="check">
                                        Completar
                                    </flux:button>
                                    <flux:button wire:click="pausar({{ $orden->id }})"
                                                 size="sm" variant="ghost" icon="pause">
                                        Pausar
                                    </flux:button>
                                @elseif($orden->estado === \App\Models\OrdenProduccion::PAUSADO)
                                    <flux:button wire:click="iniciar({{ $orden->id }})"
                                                 size="sm" variant="ghost" icon="play">
                                        Reanudar
                                    </flux:button>
                                @endif

                                <flux:button wire:click="abrirAsignar({{ $orden->id }})"
                                             size="sm" variant="ghost" icon="user-circle">
                                    Asignar
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="py-8 text-center text-zinc-400">
                            No hay órdenes de producción activas.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $ordenes->links() }}

    {{-- Modal asignar operario -------------------------------------------- --}}
    <flux:modal wire:model="modalAsignar" class="max-w-sm space-y-5">
        <flux:heading>Asignar orden #OP{{ $ordenId }}</flux:heading>

        <div class="space-y-4">
            <flux:field>
                <flux:label>Operario</flux:label>
                <flux:select wire:model.live="operarioId">
                    <flux:select.option value="">Sin asignar</flux:select.option>
                    @foreach($operarios as $op)
                        @php
                            $enProcesoOp = $op->ordenesProduccion->firstWhere('estado', 'en_proceso');
                            $pausadoOp   = $op->ordenesProduccion->firstWhere('estado', 'pausado');
                            $sufijo = match(true) {
                                (bool) $enProcesoOp && $enProcesoOp->id != $ordenId => ' — En proceso',
                                (bool) $pausadoOp   && $pausadoOp->id   != $ordenId => ' — Pausado',
                                default => '',
                            };
                        @endphp
                        <flux:select.option value="{{ $op->id }}">
                            {{ $op->name }}{{ $sufijo }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if($avisoOperario)
                    <flux:description class="text-amber-600 dark:text-amber-400">
                        ⚠ {{ $avisoOperario }}
                    </flux:description>
                @endif
            </flux:field>

            <flux:field>
                <flux:label>Prioridad</flux:label>
                <flux:select wire:model="prioridad">
                    <flux:select.option value="1">Alta</flux:select.option>
                    <flux:select.option value="2">Normal</flux:select.option>
                    <flux:select.option value="3">Baja</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Observaciones</flux:label>
                <flux:textarea wire:model="observaciones" rows="2" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalAsignar', false)" variant="ghost">
                Cancelar
            </flux:button>
            <flux:button wire:click="guardarAsignacion" variant="primary">
                Guardar
            </flux:button>
        </div>
    </flux:modal>
</div>
