<div class="space-y-6">
    <div class="flex items-center gap-3">
        <flux:button href="{{ route('pedidos.index') }}" wire:navigate variant="ghost" icon="arrow-left" />
        <flux:heading size="xl">Nuevo Pedido</flux:heading>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Columna izquierda: cliente + datos --}}
        <div class="space-y-4 lg:col-span-1">

            {{-- Búsqueda de cliente --}}
            <flux:card>
                <flux:heading size="lg" class="mb-3">Cliente</flux:heading>

                @if($clienteId)
                    <div class="flex items-center justify-between rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                        <div>
                            <flux:text class="font-semibold">{{ $clienteNombre }}</flux:text>
                        </div>
                        <flux:button wire:click="limpiarCliente" size="sm" variant="ghost" icon="x-mark" />
                    </div>
                @else
                    <div class="relative">
                        <flux:input wire:model.live.debounce.200ms="clienteBusqueda"
                                    placeholder="Buscar por nombre o teléfono…"
                                    icon="magnifying-glass" />

                        @if($this->clientesBusqueda->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach($this->clientesBusqueda as $c)
                                    <button wire:click="seleccionarCliente({{ $c->id }}, '{{ addslashes($c->nombre) }}')"
                                            class="flex w-full flex-col px-3 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="font-medium">{{ $c->nombre }}</span>
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $c->telefono }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @error('clienteId')
                        <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text>
                    @enderror
                @endif
            </flux:card>

            {{-- Datos del pedido --}}
            <flux:card>
                <flux:heading size="lg" class="mb-3">Datos</flux:heading>
                <div class="space-y-3">
                    <flux:field>
                        <flux:label>Fecha prometida</flux:label>
                        <flux:input type="date" wire:model="fechaPrometida" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Descuento (%)</flux:label>
                        <flux:input type="number" wire:model.live="descuentoPct" min="0" max="100" step="0.01" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Notas</flux:label>
                        <flux:textarea wire:model="notas" rows="3" placeholder="Instrucciones especiales…" />
                    </flux:field>
                </div>
            </flux:card>

            {{-- Resumen --}}
            <flux:card>
                <flux:heading size="lg" class="mb-3">Resumen</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <flux:text>Subtotal</flux:text>
                        <flux:text class="font-mono">${{ number_format($this->subtotal, 2) }}</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Descuento ({{ $descuentoPct }}%)</flux:text>
                        <flux:text class="font-mono text-red-500">-${{ number_format($this->descuentoMonto, 2) }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-base font-bold">
                        <flux:text>Total</flux:text>
                        <flux:text class="font-mono text-lg">${{ number_format($this->total, 2) }}</flux:text>
                    </div>
                </div>

                @error('lineas')
                    <flux:text class="mt-2 text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                <flux:button wire:click="guardar" class="mt-4 w-full" variant="primary"
                             wire:loading.attr="disabled">
                    <span wire:loading.remove>Guardar pedido</span>
                    <span wire:loading>Guardando…</span>
                </flux:button>
            </flux:card>
        </div>

        {{-- Columna derecha: productos --}}
        <div class="space-y-4 lg:col-span-2">
            {{-- Agregar línea --}}
            <flux:card>
                <flux:heading size="lg" class="mb-3">Agregar producto</flux:heading>
                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field class="sm:col-span-2">
                        <flux:label>Producto</flux:label>
                        <flux:select wire:model="addProductoId">
                            <flux:select.option value="">Seleccionar…</flux:select.option>
                            @foreach($productos as $p)
                                <flux:select.option value="{{ $p->id }}">
                                    {{ $p->nombre }} — ${{ number_format($p->precio_venta, 2) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('addProductoId')
                            <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Cantidad</flux:label>
                        <flux:input type="number" wire:model="addCantidad" min="1" />
                        @error('addCantidad')
                            <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </flux:field>

                    <flux:field class="sm:col-span-3">
                        <flux:label>Personalización (opcional)</flux:label>
                        <flux:input wire:model="addDescripcionCustom" placeholder="Color, texto, diseño…" />
                    </flux:field>
                </div>
                <flux:button wire:click="agregarLinea" class="mt-3" variant="ghost" icon="plus">
                    Agregar al pedido
                </flux:button>
            </flux:card>

            {{-- Líneas del pedido --}}
            <flux:card class="p-0">
                <div class="px-4 pt-4">
                    <flux:heading size="lg">Líneas del pedido</flux:heading>
                </div>

                @if(empty($lineas))
                    <flux:text class="py-8 text-center text-zinc-400">
                        Agrega productos al pedido.
                    </flux:text>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.row>
                                <flux:table.column>Producto</flux:table.column>
                                <flux:table.column class="text-center">Cant.</flux:table.column>
                                <flux:table.column class="text-right">Precio unit.</flux:table.column>
                                <flux:table.column class="text-right">Subtotal</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.row>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($lineas as $i => $linea)
                                <flux:table.row :key="$i">
                                    <flux:table.cell>
                                        <div class="font-medium">{{ $linea['nombre'] }}</div>
                                        @if($linea['descripcion_custom'])
                                            <div class="text-xs text-zinc-400">{{ $linea['descripcion_custom'] }}</div>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="text-center">{{ $linea['cantidad'] }}</flux:table.cell>
                                    <flux:table.cell class="text-right font-mono">
                                        ${{ number_format($linea['precio_unitario'], 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right font-mono font-semibold">
                                        ${{ number_format($linea['precio_unitario'] * $linea['cantidad'], 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button wire:click="quitarLinea({{ $i }})"
                                                     size="sm" variant="ghost" icon="trash" class="text-red-500" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </flux:card>
        </div>
    </div>
</div>
