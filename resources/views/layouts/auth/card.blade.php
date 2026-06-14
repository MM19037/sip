<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
                        <x-app-logo-icon class="size-6 fill-current text-white" />
                    </span>
                    <span class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                        Sistema Integral de Pedidos
                    </span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-zinc-700 bg-white dark:bg-zinc-800 shadow-lg">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
