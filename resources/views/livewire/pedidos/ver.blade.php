@php $pedido = $this->pedido; @endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('pedidos.index') }}" wire:navigate variant="ghost" icon="arrow-left" />
            <flux:heading size="xl">Pedido #{{ $pedido->id }}</flux:heading>
            <flux:badge :color="$pedido->estadoColor()" size="lg">{{ $pedido->estadoLabel() }}</flux:badge>
        </div>

        <div class="flex gap-2">
            @if($pedido->puedeIrAProduccion())
                <flux:button wire:click="enviarAProduccion" icon="arrow-right-circle">
                    Enviar a producción
                </flux:button>
            @endif
            @if($pedido->puedeEntregarse())
                <flux:button wire:click="marcarEntregado" variant="primary" icon="check-circle">
                    Marcar entregado
                </flux:button>
            @endif
            @if($pedido->puedeCancelarse())
                <flux:button wire:click="cancelar" variant="danger" icon="x-circle">
                    Cancelar
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Alerta de stock insuficiente --}}
    @if($pedido->estaEsperandoStock())
        <flux:card class="border border-orange-300 bg-orange-50 dark:border-orange-700 dark:bg-orange-950">
            <div class="flex items-start gap-3">
                <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-orange-500" />
                <div class="flex-1 space-y-2">
                    <flux:heading size="sm" class="text-orange-700 dark:text-orange-300">
                        Pedido bloqueado — esperando reabastecimiento de stock
                    </flux:heading>
                    @php $solicitudes = $pedido->solicitudesReabastecimiento()->with('producto')->get(); @endphp
                    @foreach($solicitudes as $sr)
                        <div class="text-sm text-orange-600 dark:text-orange-400">
                            <span class="font-medium">{{ $sr->producto->nombre }}</span>
                            — faltan <strong>{{ $sr->alerta->cantidad_faltante ?? '?' }}</strong> unidades
                            (solicitado: {{ $sr->cantidad_pedida }}, prioridad: {{ $sr->prioridadLabel() }})
                        </div>
                    @endforeach
                    @if(auth()->user()->esAdministrador())
                        <flux:button href="{{ route('inventario.solicitudes') }}" wire:navigate size="sm" variant="ghost">
                            Ver solicitudes de reabastecimiento →
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Info del pedido --}}
        <div class="space-y-4 lg:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-3">Cliente</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="font-semibold text-lg">{{ $pedido->cliente->nombre }}</div>
                    @if($pedido->cliente->telefono)
                        <flux:text>📞 {{ $pedido->cliente->telefono }}</flux:text>
                    @endif
                    @if($pedido->cliente->email)
                        <flux:text>✉️ {{ $pedido->cliente->email }}</flux:text>
                    @endif
                    @if($pedido->cliente->direccion)
                        <flux:text>📍 {{ $pedido->cliente->direccion }}</flux:text>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-3">Datos del pedido</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Fecha pedido</flux:text>
                        <flux:text>{{ $pedido->fecha_pedido->format('d/m/Y H:i') }}</flux:text>
                    </div>
                    @if($pedido->fecha_prometida)
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500 dark:text-zinc-400">Fecha prometida</flux:text>
                            <flux:text class="{{ $pedido->fecha_prometida->isPast() && !in_array($pedido->estado, ['entregado','cancelado']) ? 'font-semibold text-red-600' : '' }}">
                                {{ $pedido->fecha_prometida->format('d/m/Y') }}
                            </flux:text>
                        </div>
                    @endif
                    @if($pedido->fecha_entrega)
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500 dark:text-zinc-400">Entregado el</flux:text>
                            <flux:text>{{ $pedido->fecha_entrega->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Registrado por</flux:text>
                        <flux:text>{{ $pedido->usuario->name }}</flux:text>
                    </div>
                    @if($pedido->notas)
                        <flux:separator />
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Notas:</flux:text>
                        <flux:text>{{ $pedido->notas }}</flux:text>
                    @endif
                </div>
            </flux:card>

            {{-- Totales --}}
            <flux:card>
                <flux:heading size="lg" class="mb-3">Totales</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <flux:text>Subtotal</flux:text>
                        <flux:text class="font-mono">${{ number_format($pedido->subtotal, 2) }}</flux:text>
                    </div>
                    @if($pedido->descuento > 0)
                        <div class="flex justify-between">
                            <flux:text>Descuento</flux:text>
                            <flux:text class="font-mono text-red-500">-${{ number_format($pedido->descuento, 2) }}</flux:text>
                        </div>
                    @endif
                    <flux:separator />
                    <div class="flex justify-between text-base font-bold">
                        <flux:text>Total</flux:text>
                        <flux:text class="font-mono">${{ number_format($pedido->total, 2) }}</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Costo</flux:text>
                        <flux:text class="font-mono">${{ number_format($pedido->total_costo, 2) }}</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Ganancia</flux:text>
                        <flux:text class="font-mono text-lime-600 font-semibold">${{ number_format($pedido->ganancia, 2) }}</flux:text>
                    </div>
                </div>
            </flux:card>

            {{-- Orden de producción --}}
            @if($pedido->ordenProduccion)
                @php $op = $pedido->ordenProduccion; @endphp
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Producción</flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500 dark:text-zinc-400">Estado</flux:text>
                            <flux:badge :color="$op->estadoColor()" size="sm">{{ $op->estadoLabel() }}</flux:badge>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500 dark:text-zinc-400">Prioridad</flux:text>
                            <flux:badge :color="$op->prioridadColor()" size="sm">{{ $op->prioridadLabel() }}</flux:badge>
                        </div>
                        @if($op->operario)
                            <div class="flex justify-between">
                                <flux:text class="text-zinc-500 dark:text-zinc-400">Operario</flux:text>
                                <flux:text>{{ $op->operario->name }}</flux:text>
                            </div>
                        @endif
                        @if($op->tiempo_minutos)
                            <div class="flex justify-between">
                                <flux:text class="text-zinc-500 dark:text-zinc-400">Tiempo</flux:text>
                                <flux:text>{{ $op->tiempo_minutos }} min</flux:text>
                            </div>
                        @endif
                        @if($op->observaciones)
                            <flux:text class="text-zinc-400">{{ $op->observaciones }}</flux:text>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Detalle de productos --}}
        <div class="lg:col-span-2">
            <flux:card class="p-0">
                <div class="px-4 pt-4 pb-2">
                    <flux:heading size="lg">Productos del pedido</flux:heading>
                </div>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column class="text-center">Cant.</flux:table.column>
                        <flux:table.column class="text-right">Precio unit.</flux:table.column>
                        <flux:table.column class="text-right">Subtotal</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($pedido->detalles as $d)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="font-medium">{{ $d->producto->nombre }}</div>
                                    @if($d->descripcion_custom)
                                        <div class="text-xs text-zinc-400">{{ $d->descripcion_custom }}</div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-center">{{ $d->cantidad }}</flux:table.cell>
                                <flux:table.cell class="text-right font-mono">${{ number_format($d->precio_unitario, 2) }}</flux:table.cell>
                                <flux:table.cell class="text-right font-mono font-semibold">${{ number_format($d->subtotal, 2) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    </div>
</div>
