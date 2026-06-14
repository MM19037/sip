<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Inventario — Movimientos</flux:heading>
        <div class="flex items-center gap-2">
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" variant="ghost" size="sm">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-arrow-down"
                        href="{{ route('reportes.inventario.movimientos', ['formato' => 'pdf']) }}"
                        target="_blank">
                        Descargar PDF
                    </flux:menu.item>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('reportes.inventario.movimientos', ['formato' => 'csv']) }}">
                        Descargar CSV
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button wire:click="abrirModal" icon="plus">Registrar movimiento</flux:button>
        </div>
    </div>

    {{-- Alertas generales de stock bajo --}}
    @if($alertasGenerales->isNotEmpty())
        <flux:card class="border border-red-300 dark:border-red-700">
            <div class="mb-3 flex items-center gap-2">
                <flux:heading size="lg" class="text-red-600">Alertas de stock bajo</flux:heading>
                <flux:badge color="red">{{ $alertasGenerales->count() }}</flux:badge>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column class="text-center">Stock total</flux:table.column>
                    <flux:table.column class="text-center">Disponible</flux:table.column>
                    <flux:table.column class="text-center">Mínimo</flux:table.column>
                    <flux:table.column class="text-center">Desde</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($alertasGenerales as $a)
                        <flux:table.row>
                            <flux:table.cell class="font-medium">{{ $a->producto->nombre }}</flux:table.cell>
                            <flux:table.cell>{{ $a->producto->categoria->nombre }}</flux:table.cell>
                            <flux:table.cell class="text-center">{{ $a->producto->stock_actual }}</flux:table.cell>
                            <flux:table.cell class="text-center font-bold text-red-600">
                                {{ $a->producto->stockDisponible() }}
                            </flux:table.cell>
                            <flux:table.cell class="text-center">{{ $a->stock_minimo }}</flux:table.cell>
                            <flux:table.cell class="text-center text-sm text-zinc-400">
                                {{ $a->created_at->format('d/m/Y') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button wire:click="resolverAlerta({{ $a->id }})" size="sm" variant="ghost">
                                    Resolver
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Pedidos bloqueados por falta de stock --}}
    @if($alertasPedidos->isNotEmpty())
        <flux:card class="border border-orange-300 dark:border-orange-700">
            <div class="mb-3 flex items-center gap-2">
                <flux:heading size="lg" class="text-orange-600">Pedidos esperando reabastecimiento</flux:heading>
                <flux:badge color="orange">{{ $alertasPedidos->count() }}</flux:badge>
            </div>
            <flux:text class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
                Al registrar una entrada de stock, el sistema libera automáticamente los pedidos que queden cubiertos.
            </flux:text>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Pedido</flux:table.column>
                    <flux:table.column>Producto faltante</flux:table.column>
                    <flux:table.column class="text-center">Disponible</flux:table.column>
                    <flux:table.column class="text-center">Faltan</flux:table.column>
                    <flux:table.column class="text-center">Desde</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($alertasPedidos as $a)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('pedidos.ver', $a->pedido_id) }}" wire:navigate
                                   class="font-mono text-blue-600 hover:underline dark:text-blue-400">
                                    #{{ $a->pedido_id }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $a->producto->nombre }}</flux:table.cell>
                            <flux:table.cell class="text-center text-red-600 font-semibold">
                                {{ $a->producto->stockDisponible() }}
                            </flux:table.cell>
                            <flux:table.cell class="text-center font-bold text-orange-600">
                                {{ $a->cantidad_faltante }}
                            </flux:table.cell>
                            <flux:table.cell class="text-center text-sm text-zinc-400">
                                {{ $a->created_at->format('d/m/Y') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3">
        <flux:input wire:model.live.debounce.300ms="busqueda" placeholder="Buscar producto…" icon="magnifying-glass" class="w-56" />
        <flux:select wire:model.live="filtroTipo" class="w-40">
            <flux:select.option value="">Todos</flux:select.option>
            <flux:select.option value="entrada">Entradas</flux:select.option>
            <flux:select.option value="salida">Salidas</flux:select.option>
            <flux:select.option value="ajuste">Ajustes</flux:select.option>
        </flux:select>
    </div>

    {{-- Tabla --}}
    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Producto</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column class="text-center">Cantidad</flux:table.column>
                <flux:table.column class="text-right">Costo unit.</flux:table.column>
                <flux:table.column>Motivo</flux:table.column>
                <flux:table.column>Usuario</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($movimientos as $m)
                    <flux:table.row>
                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $m->fecha->format('d/m/Y H:i') }}
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $m->producto->nombre }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$m->tipoColor()" size="sm">{{ $m->tipoLabel() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-center font-mono {{ $m->cantidad >= 0 ? 'text-lime-600' : 'text-red-600' }}">
                            {{ $m->cantidad >= 0 ? '+' : '' }}{{ $m->cantidad }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right font-mono">${{ number_format($m->costo_unitario, 2) }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $m->motivo }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">{{ $m->usuario->name }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-8 text-center text-zinc-400">
                            No hay movimientos registrados.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $movimientos->links() }}

    {{-- Modal registrar movimiento --}}
    <flux:modal wire:model="modalAbierto" class="max-w-md space-y-6">
        <flux:heading>Registrar movimiento de inventario</flux:heading>

        <div class="space-y-4">
            <flux:field>
                <flux:label>Producto *</flux:label>
                <flux:select wire:model="productoId">
                    <flux:select.option value="">Seleccionar…</flux:select.option>
                    @foreach($productos as $p)
                        <flux:select.option value="{{ $p->id }}">
                            {{ $p->nombre }} (disponible: {{ $p->stockDisponible() }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('productoId') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Tipo *</flux:label>
                <flux:select wire:model="tipo">
                    <flux:select.option value="entrada">Entrada (compra)</flux:select.option>
                    <flux:select.option value="salida">Salida (uso/pérdida)</flux:select.option>
                    <flux:select.option value="ajuste">Ajuste de inventario</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Cantidad *</flux:label>
                <flux:input type="number" wire:model="cantidad" min="1" />
                <flux:description>Para salidas/ajustes negativos ingresa el valor positivo; el sistema lo registrará como negativo.</flux:description>
                @error('cantidad') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Costo unitario ($)</flux:label>
                <flux:input type="number" wire:model="costoUnitario" min="0" step="0.01" />
            </flux:field>

            <flux:field>
                <flux:label>Motivo</flux:label>
                <flux:input wire:model="motivo" placeholder="Compra a proveedor, ajuste de conteo, etc." />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalAbierto', false)" variant="ghost">Cancelar</flux:button>
            <flux:button wire:click="guardar" variant="primary">Registrar</flux:button>
        </div>
    </flux:modal>
</div>
