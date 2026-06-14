<div class="space-y-6">
    <flux:heading size="xl">Dashboard</flux:heading>

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('pedidos.index') }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-blue-400">
                <flux:heading size="lg" class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                    {{ $stats->pedidos_activos ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">Pedidos activos</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('pedidos.index', ['filtroEstado' => 'esperando_stock']) }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-orange-400 {{ ($stats->esperando_stock ?? 0) > 0 ? 'ring-2 ring-orange-400' : '' }}">
                <flux:heading size="lg" class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                    {{ $stats->esperando_stock ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">Esperando stock</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('pedidos.index', ['filtroEstado' => 'pendiente']) }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-yellow-400">
                <flux:heading size="lg" class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ $stats->pedidos_pendientes ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">Pendientes</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('produccion.index', ['filtroEstado' => 'en_proceso']) }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-indigo-400">
                <flux:heading size="lg" class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $stats->en_produccion ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">En producción</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('pedidos.index', ['filtroEstado' => 'listo']) }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-lime-400">
                <flux:heading size="lg" class="text-3xl font-bold text-lime-600 dark:text-lime-400">
                    {{ $stats->listos_para_entrega ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">Listos para entrega</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('pedidos.index', ['filtroEstado' => 'entregado']) }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-zinc-400">
                <flux:heading size="lg" class="text-3xl font-bold text-zinc-600 dark:text-zinc-300">
                    {{ $stats->entregados_hoy ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">Entregados hoy</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('costos.rentabilidad') }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-emerald-400">
                <flux:heading size="lg" class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                    ${{ number_format($stats->ventas_mes ?? 0, 2) }}
                </flux:heading>
                <flux:text class="text-sm">Ventas del mes</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('costos.rentabilidad') }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-teal-400">
                <flux:heading size="lg" class="text-3xl font-bold text-teal-600 dark:text-teal-400">
                    ${{ number_format($stats->ganancia_mes ?? 0, 2) }}
                </flux:heading>
                <flux:text class="text-sm">Ganancia del mes</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('inventario.solicitudes') }}" wire:navigate>
            <flux:card class="text-center transition hover:ring-2 hover:ring-red-400 {{ ($stats->alertas_stock_activas ?? 0) > 0 ? 'ring-2 ring-red-400' : '' }}">
                <flux:heading size="lg" class="text-3xl font-bold text-red-600 dark:text-red-400">
                    {{ $stats->alertas_stock_activas ?? 0 }}
                </flux:heading>
                <flux:text class="text-sm">Alertas de stock</flux:text>
            </flux:card>
        </a>
    </div>

    {{-- Gráficas --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Donut: pedidos por estado --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Pedidos por estado</flux:heading>
            <div class="flex items-center justify-center" style="height:260px">
                <canvas
                    x-data="{
                        init() {
                            const dark   = document.documentElement.classList.contains('dark');
                            const txtColor  = dark ? '#ddeaf6' : '#1e293b';
                            const estados = @js(array_keys($charts['pedidosPorEstado']->toArray()));
                            const totales = @js(array_values($charts['pedidosPorEstado']->toArray()));
                            const labels = {
                                esperando_stock: 'Esp. stock', pendiente: 'Pendiente',
                                en_produccion: 'En producción', listo: 'Listo',
                                entregado: 'Entregado', cancelado: 'Cancelado'
                            };
                            const colors = {
                                esperando_stock: '#f97316', pendiente: '#eab308',
                                en_produccion: '#6366f1', listo: '#84cc16',
                                entregado: '#22c55e', cancelado: '#6b7280'
                            };
                            const chart = new Chart(this.$el, {
                                type: 'doughnut',
                                data: {
                                    labels: estados.map(e => labels[e] ?? e),
                                    datasets: [{ data: totales, backgroundColor: estados.map(e => colors[e] ?? '#94a3b8'), borderWidth: 0 }]
                                },
                                options: {
                                    cutout: '65%',
                                    plugins: { legend: { position: 'right', labels: { color: txtColor, boxWidth: 12, padding: 12 } } },
                                    maintainAspectRatio: false
                                }
                            });
                            this.$cleanup(() => chart.destroy());
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>

        {{-- Barras: ventas del mes por día --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Ventas del mes — {{ now()->translatedFormat('F Y') }}</flux:heading>
            <div style="height:260px">
                <canvas
                    x-data="{
                        init() {
                            const dark     = document.documentElement.classList.contains('dark');
                            const tickColor = dark ? '#8fb2cf' : '#475569';
                            const gridColor = dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.07)';
                            const chart = new Chart(this.$el, {
                                type: 'bar',
                                data: {
                                    labels: @js($charts['diasLabels']),
                                    datasets: [{
                                        label: 'Ventas ($)',
                                        data: @js($charts['ventasMes']),
                                        backgroundColor: '#0f7ef4cc',
                                        borderColor: '#0f7ef4',
                                        borderWidth: 1,
                                        borderRadius: 4
                                    }]
                                },
                                options: {
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                                        y: { ticks: { color: tickColor }, grid: { color: gridColor } }
                                    }
                                }
                            });
                            this.$cleanup(() => chart.destroy());
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>

        {{-- Línea: entradas/salidas del mes --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Movimientos de inventario — {{ now()->translatedFormat('F') }}</flux:heading>
            <div style="height:260px">
                <canvas
                    x-data="{
                        init() {
                            const dark      = document.documentElement.classList.contains('dark');
                            const txtColor  = dark ? '#ddeaf6' : '#1e293b';
                            const tickColor = dark ? '#8fb2cf' : '#475569';
                            const gridColor = dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.07)';
                            const chart = new Chart(this.$el, {
                                type: 'line',
                                data: {
                                    labels: @js($charts['diasLabels']),
                                    datasets: [
                                        {
                                            label: 'Entradas',
                                            data: @js($charts['entradas']),
                                            borderColor: '#84cc16',
                                            backgroundColor: '#84cc1620',
                                            fill: true, tension: 0.3, pointRadius: 3
                                        },
                                        {
                                            label: 'Salidas',
                                            data: @js($charts['salidas']),
                                            borderColor: '#ef4444',
                                            backgroundColor: '#ef444420',
                                            fill: true, tension: 0.3, pointRadius: 3
                                        }
                                    ]
                                },
                                options: {
                                    maintainAspectRatio: false,
                                    plugins: { legend: { labels: { color: txtColor, boxWidth: 12 } } },
                                    scales: {
                                        x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                                        y: { ticks: { color: tickColor }, grid: { color: gridColor }, beginAtZero: true }
                                    }
                                }
                            });
                            this.$cleanup(() => chart.destroy());
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>

        {{-- Barras agrupadas: stock actual vs mínimo por categoría --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Stock por categoría</flux:heading>
            <div style="height:260px">
                <canvas
                    x-data="{
                        init() {
                            const dark      = document.documentElement.classList.contains('dark');
                            const txtColor  = dark ? '#ddeaf6' : '#1e293b';
                            const tickColor = dark ? '#8fb2cf' : '#475569';
                            const gridColor = dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.07)';
                            const chart = new Chart(this.$el, {
                                type: 'bar',
                                data: {
                                    labels: @js($charts['categorias']),
                                    datasets: [
                                        {
                                            label: 'Stock actual',
                                            data: @js($charts['stockActual']),
                                            backgroundColor: '#0f7ef4cc',
                                            borderRadius: 4
                                        },
                                        {
                                            label: 'Stock mínimo',
                                            data: @js($charts['stockMinimo']),
                                            backgroundColor: '#f97316aa',
                                            borderRadius: 4
                                        }
                                    ]
                                },
                                options: {
                                    maintainAspectRatio: false,
                                    plugins: { legend: { labels: { color: txtColor, boxWidth: 12 } } },
                                    scales: {
                                        x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                                        y: { ticks: { color: tickColor }, grid: { color: gridColor }, beginAtZero: true }
                                    }
                                }
                            });
                            this.$cleanup(() => chart.destroy());
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Pedidos activos recientes --}}
        <flux:card>
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Pedidos activos</flux:heading>
                <flux:button href="{{ route('pedidos.index') }}" wire:navigate size="sm" variant="ghost">
                    Ver todos
                </flux:button>
            </div>

            @if($pedidosActivos->isEmpty())
                <flux:text class="py-4 text-center text-zinc-400">No hay pedidos activos.</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>#</flux:table.column>
                        <flux:table.column>Cliente</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column class="text-right">Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($pedidosActivos as $p)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('pedidos.ver', $p->id) }}" wire:navigate class="font-mono text-blue-600 hover:underline dark:text-blue-400">
                                        #{{ $p->id }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $p->cliente }}</flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $color = match($p->estado) {
                                            'esperando_stock' => 'orange', 'pendiente' => 'yellow',
                                            'en_produccion' => 'blue', 'listo' => 'lime', default => 'zinc'
                                        };
                                        $label = match($p->estado) {
                                            'esperando_stock' => 'Esp. stock', 'pendiente' => 'Pendiente',
                                            'en_produccion' => 'En prod.', 'listo' => 'Listo', default => $p->estado
                                        };
                                    @endphp
                                    <flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">${{ number_format($p->total, 2) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        {{-- Alertas de inventario --}}
        <flux:card>
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Alertas de inventario</flux:heading>
                <flux:button href="{{ route('inventario.movimientos') }}" wire:navigate size="sm" variant="ghost">
                    Gestionar
                </flux:button>
            </div>

            @if($alertas->isEmpty())
                <flux:text class="py-4 text-center text-zinc-400">Sin alertas de stock.</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column class="text-center">Stock</flux:table.column>
                        <flux:table.column class="text-center">Mínimo</flux:table.column>
                        <flux:table.column class="text-center">Faltantes</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($alertas as $a)
                            <flux:table.row>
                                <flux:table.cell class="font-medium">{{ $a->nombre }}</flux:table.cell>
                                <flux:table.cell class="text-center text-red-600">{{ $a->stock_actual }}</flux:table.cell>
                                <flux:table.cell class="text-center">{{ $a->stock_minimo }}</flux:table.cell>
                                <flux:table.cell class="text-center font-bold text-red-600">{{ $a->unidades_faltantes }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    </div>
</div>
