<div class="space-y-6">
    <flux:heading size="xl">Dashboard</flux:heading>

    @php
        $u     = auth()->user();
        $admin = $u->esAdministrador();
        $puede = fn(string $s) => $admin || \App\Models\PermisoRol::tiene($u->rol, $s);
    @endphp

    {{-- Tarjetas agrupadas por módulo — 2×2 --}}
    <div class="grid gap-4 lg:grid-cols-2">

        {{-- PEDIDOS --}}
        @if($puede('pedidos'))
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-700">
                <flux:icon name="clipboard-document-list" class="size-4 text-blue-500" />
                <flux:heading size="sm" class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Pedidos</flux:heading>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <a href="{{ route('pedidos.index') }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-blue-400 dark:border-zinc-700 dark:group-hover:border-blue-500">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats->pedidos_activos ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Activos</div>
                    </div>
                </a>
                <a href="{{ route('pedidos.index', ['filtroEstado' => 'esperando_stock']) }}" wire:navigate class="group">
                    <div class="rounded-lg border p-3 text-center transition group-hover:border-orange-400 dark:group-hover:border-orange-500 {{ ($stats->esperando_stock ?? 0) > 0 ? 'border-orange-400 dark:border-orange-500' : 'border-zinc-100 dark:border-zinc-700' }}">
                        <div class="text-2xl font-bold text-orange-500 dark:text-orange-400">{{ $stats->esperando_stock ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Esp. stock</div>
                    </div>
                </a>
                <a href="{{ route('pedidos.index', ['filtroEstado' => 'pendiente']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-yellow-400 dark:border-zinc-700 dark:group-hover:border-yellow-500">
                        <div class="text-2xl font-bold text-yellow-500 dark:text-yellow-400">{{ $stats->pedidos_pendientes ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Pendientes</div>
                    </div>
                </a>
                <a href="{{ route('pedidos.index', ['filtroEstado' => 'en_produccion']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-indigo-400 dark:border-zinc-700 dark:group-hover:border-indigo-500">
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats->en_produccion ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">En producción</div>
                    </div>
                </a>
                <a href="{{ route('pedidos.index', ['filtroEstado' => 'listo']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-lime-400 dark:border-zinc-700 dark:group-hover:border-lime-500">
                        <div class="text-2xl font-bold text-lime-600 dark:text-lime-400">{{ $stats->listos_para_entrega ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Listos</div>
                    </div>
                </a>
                <a href="{{ route('pedidos.index', ['filtroEstado' => 'entregado']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-zinc-400 dark:border-zinc-700 dark:group-hover:border-zinc-500">
                        <div class="text-2xl font-bold text-zinc-600 dark:text-zinc-300">{{ $stats->entregados_hoy ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Entregados hoy</div>
                    </div>
                </a>
            </div>
        </flux:card>
        @endif

        {{-- PRODUCCIÓN --}}
        @if($puede('produccion'))
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-700">
                <flux:icon name="wrench-screwdriver" class="size-4 text-violet-500" />
                <flux:heading size="sm" class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Producción</flux:heading>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <a href="{{ route('produccion.index', ['filtroEstado' => 'asignado']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-violet-400 dark:border-zinc-700 dark:group-hover:border-violet-500">
                        <div class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ $ordenesStats['asignado'] ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Asignadas</div>
                    </div>
                </a>
                <a href="{{ route('produccion.index', ['filtroEstado' => 'en_proceso']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-blue-400 dark:border-zinc-700 dark:group-hover:border-blue-500">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $ordenesStats['en_proceso'] ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">En proceso</div>
                    </div>
                </a>
                <a href="{{ route('produccion.index', ['filtroEstado' => 'pausado']) }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-amber-400 dark:border-zinc-700 dark:group-hover:border-amber-500">
                        <div class="text-2xl font-bold text-amber-500 dark:text-amber-400">{{ $ordenesStats['pausado'] ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Pausadas</div>
                    </div>
                </a>
            </div>
        </flux:card>
        @endif

        {{-- COSTOS --}}
        @if($puede('costos'))
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-700">
                <flux:icon name="currency-dollar" class="size-4 text-emerald-500" />
                <flux:heading size="sm" class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Costos — {{ now()->translatedFormat('F Y') }}</flux:heading>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('costos.rentabilidad') }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-emerald-400 dark:border-zinc-700 dark:group-hover:border-emerald-500">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($stats->ventas_mes ?? 0, 2) }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Ventas del mes</div>
                    </div>
                </a>
                <a href="{{ route('costos.rentabilidad') }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-teal-400 dark:border-zinc-700 dark:group-hover:border-teal-500">
                        <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">${{ number_format($stats->ganancia_mes ?? 0, 2) }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Ganancia del mes</div>
                    </div>
                </a>
            </div>
        </flux:card>
        @endif

        {{-- INVENTARIO --}}
        @if($puede('inventario.solicitudes'))
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-700">
                <flux:icon name="archive-box" class="size-4 text-red-500" />
                <flux:heading size="sm" class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Inventario</flux:heading>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('inventario.solicitudes') }}" wire:navigate class="group">
                    <div class="rounded-lg border p-3 text-center transition group-hover:border-red-400 dark:group-hover:border-red-500 {{ ($stats->alertas_stock_activas ?? 0) > 0 ? 'border-red-400 dark:border-red-500' : 'border-zinc-100 dark:border-zinc-700' }}">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats->alertas_stock_activas ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Alertas de stock</div>
                    </div>
                </a>
                <a href="{{ route('inventario.productos') }}" wire:navigate class="group">
                    <div class="rounded-lg border border-zinc-100 p-3 text-center transition group-hover:border-orange-400 dark:border-zinc-700 dark:group-hover:border-orange-500">
                        <div class="text-2xl font-bold text-orange-500 dark:text-orange-400">{{ $stats->productos_bajo_stock ?? 0 }}</div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Bajo stock mínimo</div>
                    </div>
                </a>
            </div>
        </flux:card>
        @endif

    </div>

    {{-- Gráficas --}}
    @php
        $hayGraficas = $puede('pedidos') || $puede('costos') || $puede('inventario');
    @endphp
    @if($hayGraficas)
    <div class="grid gap-6 lg:grid-cols-2">

        @if($puede('pedidos'))
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
                            this.$el.addEventListener('livewire:navigating', () => chart.destroy(), { once: true });
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>
        @endif

        @if($puede('costos'))
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
                            this.$el.addEventListener('livewire:navigating', () => chart.destroy(), { once: true });
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>
        @endif

        @if($puede('inventario'))
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
                            this.$el.addEventListener('livewire:navigating', () => chart.destroy(), { once: true });
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>

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
                            this.$el.addEventListener('livewire:navigating', () => chart.destroy(), { once: true });
                        }
                    }"
                ></canvas>
            </div>
        </flux:card>
        @endif

    </div>
    @endif

    @if($puede('pedidos') || $puede('inventario'))
    <div class="grid gap-6 lg:grid-cols-2">
        @if($puede('pedidos'))
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
        @endif

        @if($puede('inventario'))
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
        @endif
    </div>
    @endif
</div>
