<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Inventario — Productos</flux:heading>
        <div class="flex items-center gap-2">
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" variant="ghost" size="sm">Exportar</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-arrow-down"
                        href="{{ route('reportes.inventario.productos', ['formato' => 'pdf']) }}"
                        target="_blank">
                        Descargar PDF
                    </flux:menu.item>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('reportes.inventario.productos', ['formato' => 'csv']) }}">
                        Descargar CSV
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button wire:click="abrirCrear" icon="plus">Nuevo producto</flux:button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3">
        <flux:input wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar producto…"
                    icon="magnifying-glass"
                    class="w-56" />

        <flux:select wire:model.live="filtroCategoria" class="w-48">
            <flux:select.option value="">Todas las categorías</flux:select.option>
            @foreach($categorias as $cat)
                <flux:select.option value="{{ $cat->id }}">{{ $cat->nombre }}</flux:select.option>
            @endforeach
        </flux:select>

        <label class="flex items-center gap-2 text-sm">
            <flux:checkbox wire:model.live="soloActivos" />
            Solo activos
        </label>
    </div>

    {{-- Tabla --}}
    <flux:card class="p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Producto</flux:table.column>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column class="text-right">Costo</flux:table.column>
                <flux:table.column class="text-right">Margen %</flux:table.column>
                <flux:table.column class="text-right">Precio venta</flux:table.column>
                <flux:table.column class="text-center">Stock total</flux:table.column>
                <flux:table.column class="text-center">Reservado</flux:table.column>
                <flux:table.column class="text-center">Disponible</flux:table.column>
                <flux:table.column class="text-center">Mín.</flux:table.column>
                <flux:table.column class="text-center">Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($productos as $p)
                    <flux:table.row :key="$p->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $p->nombre }}</div>
                            @if($p->descripcion)
                                <div class="text-xs text-zinc-400 max-w-xs truncate">{{ $p->descripcion }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ $p->categoria->nombre }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right font-mono">${{ number_format($p->costo_base, 2) }}</flux:table.cell>
                        <flux:table.cell class="text-right">{{ $p->margen_ganancia }}%</flux:table.cell>
                        <flux:table.cell class="text-right font-mono font-semibold">${{ number_format($p->precio_venta, 2) }}</flux:table.cell>
                        <flux:table.cell class="text-center">
                            <span class="text-zinc-700 dark:text-zinc-200">{{ $p->stock_actual }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            @if($p->stock_reservado > 0)
                                <span class="font-semibold text-orange-500">{{ $p->stock_reservado }}</span>
                            @else
                                <span class="text-zinc-400">0</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            @php $disponible = $p->stockDisponible(); @endphp
                            <span class="{{ $disponible <= $p->stock_minimo ? 'font-bold text-red-600' : 'text-zinc-700 dark:text-zinc-200' }}">
                                {{ $disponible }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-center text-zinc-400">{{ $p->stock_minimo }}</flux:table.cell>
                        <flux:table.cell class="text-center">
                            @if($p->stockDisponible() <= 0)
                                <flux:badge color="red" size="sm">Sin stock</flux:badge>
                            @elseif($p->bajoStockDisponible())
                                <flux:badge color="orange" size="sm">Stock bajo</flux:badge>
                            @elseif($p->activo)
                                <flux:badge color="lime" size="sm">OK</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="abrirEditar({{ $p->id }})" icon="pencil">
                                        Editar
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="toggleActivo({{ $p->id }})"
                                                    icon="{{ $p->activo ? 'eye-slash' : 'eye' }}">
                                        {{ $p->activo ? 'Desactivar' : 'Activar' }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="11" class="py-8 text-center text-zinc-400">
                            No se encontraron productos.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $productos->links() }}

    {{-- Modal crear / editar --}}
    <flux:modal wire:model="modalAbierto" class="max-w-lg space-y-6">
        <flux:heading>{{ $editandoId ? 'Editar producto' : 'Nuevo producto' }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field class="sm:col-span-2">
                <flux:label>Nombre *</flux:label>
                <flux:input wire:model="nombre" />
                @error('nombre') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Categoría *</flux:label>
                <flux:select wire:model="categoriaId">
                    <flux:select.option value="">Seleccionar categoría…</flux:select.option>
                    @foreach($categorias as $cat)
                        <flux:select.option value="{{ $cat->id }}">{{ $cat->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('categoriaId') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Stock mínimo</flux:label>
                <flux:input type="number" wire:model="stockMinimo" min="0" />
            </flux:field>

            <flux:field>
                <flux:label>Costo base ($) *</flux:label>
                <flux:input type="number" wire:model="costoBase" min="0" step="0.01" />
                @error('costoBase') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Margen de ganancia (%) *</flux:label>
                <flux:input type="number" wire:model="margenGanancia" min="0" step="0.01" />
                @error('margenGanancia') <flux:text class="text-sm text-red-500">{{ $message }}</flux:text> @enderror
            </flux:field>

            @if($costoBase > 0)
                <div class="sm:col-span-2 rounded-lg bg-blue-50 p-3 text-sm dark:bg-blue-900/20">
                    Precio de venta calculado:
                    <span class="font-bold text-blue-700 dark:text-blue-300">
                        ${{ number_format($costoBase * (1 + $margenGanancia / 100), 2) }}
                    </span>
                </div>
            @endif

            <flux:field class="sm:col-span-2">
                <flux:label>Descripción</flux:label>
                <flux:textarea wire:model="descripcion" rows="2" />
            </flux:field>

            <flux:field>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <flux:checkbox wire:model="activo" />
                    Producto activo
                </label>
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="$set('modalAbierto', false)" variant="ghost">Cancelar</flux:button>
            <flux:button wire:click="guardar" variant="primary">Guardar</flux:button>
        </div>
    </flux:modal>
</div>
