<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-800 dark:text-zinc-100">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-100 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @php
                $u     = auth()->user();
                $admin = $u->esAdministrador();
                $puede = fn(string $s) => $admin || \App\Models\PermisoRol::tiene($u->rol, $s);
            @endphp

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Principal')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Dashboard
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if($puede('pedidos') || $puede('clientes'))
                    <flux:sidebar.group :heading="__('Recepción')" class="grid">
                        @if($puede('pedidos'))
                            <flux:sidebar.item icon="clipboard-document-list"
                                :href="route('pedidos.index')"
                                :current="request()->routeIs('pedidos.*')"
                                wire:navigate>
                                Pedidos
                            </flux:sidebar.item>
                        @endif
                        @if($puede('clientes'))
                            <flux:sidebar.item icon="users"
                                :href="route('clientes.index')"
                                :current="request()->routeIs('clientes.*')"
                                wire:navigate>
                                Clientes
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                @if($puede('inventario'))
                    <flux:sidebar.group :heading="__('Inventario')" class="grid">
                        <flux:sidebar.item icon="archive-box"
                            :href="route('inventario.productos')"
                            :current="request()->routeIs('inventario.productos')"
                            wire:navigate>
                            Productos
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="arrows-up-down"
                            :href="route('inventario.movimientos')"
                            :current="request()->routeIs('inventario.movimientos')"
                            wire:navigate>
                            Movimientos
                        </flux:sidebar.item>
                        @if($puede('inventario.solicitudes'))
                            <flux:sidebar.item icon="inbox-stack"
                                :href="route('inventario.solicitudes')"
                                :current="request()->routeIs('inventario.solicitudes')"
                                wire:navigate>
                                Solicitudes
                            </flux:sidebar.item>
                        @endif
                        @if($puede('categorias'))
                            <flux:sidebar.item icon="squares-2x2"
                                :href="route('categorias.index')"
                                :current="request()->routeIs('categorias.*')"
                                wire:navigate>
                                Categorías
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                @if($puede('produccion'))
                    <flux:sidebar.group :heading="__('Producción')" class="grid">
                        <flux:sidebar.item icon="wrench-screwdriver"
                            :href="route('produccion.index')"
                            :current="request()->routeIs('produccion.*')"
                            wire:navigate>
                            Órdenes
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if($puede('costos'))
                    <flux:sidebar.group :heading="__('Costos y Precios')" class="grid">
                        <flux:sidebar.item icon="chart-bar"
                            :href="route('costos.valoracion')"
                            :current="request()->routeIs('costos.valoracion')"
                            wire:navigate>
                            Valoración
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="tag"
                            :href="route('costos.lotes')"
                            :current="request()->routeIs('costos.lotes')"
                            wire:navigate>
                            Lotes
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="presentation-chart-line"
                            :href="route('costos.rentabilidad')"
                            :current="request()->routeIs('costos.rentabilidad')"
                            wire:navigate>
                            Rentabilidad
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if($admin)
                    <flux:sidebar.group :heading="__('Administración')" class="grid">
                        <flux:sidebar.item icon="user-group"
                            :href="route('usuarios.index')"
                            :current="request()->routeIs('usuarios.*')"
                            wire:navigate>
                            Usuarios
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="shield-check"
                            :href="route('permisos.index')"
                            :current="request()->routeIs('permisos.*')"
                            wire:navigate>
                            Permisos
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Botón de tema — desktop --}}
            <div x-data>
                <flux:sidebar.profile name="Modo claro" avatar:icon="sun" :chevron="false"
                    x-show="$flux.appearance === 'dark'" x-cloak
                    x-on:click="$flux.appearance = 'light'" />
                <flux:sidebar.profile name="Modo oscuro" avatar:icon="moon" :chevron="false"
                    x-show="$flux.appearance !== 'dark'"
                    x-on:click="$flux.appearance = 'dark'" />
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            {{-- Botón de tema — móvil --}}
            <div x-data>
                <flux:button variant="ghost" size="sm" icon="sun"
                    x-show="$flux.appearance === 'dark'" x-cloak
                    x-on:click="$flux.appearance = 'light'">Modo claro</flux:button>
                <flux:button variant="ghost" size="sm" icon="moon"
                    x-show="$flux.appearance !== 'dark'"
                    x-on:click="$flux.appearance = 'dark'">Modo oscuro</flux:button>
            </div>
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
