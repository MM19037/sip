<?php

use App\Http\Controllers\Reportes\CostosController;
use App\Http\Controllers\Reportes\InventarioController;
use App\Livewire\Categorias;
use App\Livewire\Clientes;
use App\Livewire\Costos;
use App\Livewire\Dashboard;
use App\Livewire\Inventario;
use App\Livewire\Pedidos;
use App\Livewire\Produccion;
use App\Livewire\Usuarios;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard — todos los roles
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Pedidos — administrador + recepcionista
    Route::middleware(['role:administrador,recepcionista'])->group(function () {
        Route::get('/pedidos', Pedidos\Index::class)->name('pedidos.index');
        Route::get('/pedidos/crear', Pedidos\Crear::class)->name('pedidos.crear');
        Route::get('/pedidos/{pedido}', Pedidos\Ver::class)->name('pedidos.ver');
    });

    // Inventario — administrador + recepcionista
    Route::middleware(['role:administrador,recepcionista'])->group(function () {
        Route::get('/inventario/productos', Inventario\Productos::class)->name('inventario.productos');
        Route::get('/inventario/movimientos', Inventario\Movimientos::class)->name('inventario.movimientos');
    });

    // Producción — administrador + produccion
    Route::middleware(['role:administrador,produccion'])->group(function () {
        Route::get('/produccion', Produccion\Index::class)->name('produccion.index');
    });

    // Clientes — administrador + recepcionista
    Route::middleware(['role:administrador,recepcionista'])->group(function () {
        Route::get('/clientes', Clientes\Index::class)->name('clientes.index');
    });

    // Solicitudes de reabastecimiento + Usuarios + Costos — solo administrador
    Route::middleware(['role:administrador'])->group(function () {
        Route::get('/inventario/solicitudes', Inventario\Solicitudes::class)->name('inventario.solicitudes');
        Route::get('/usuarios', Usuarios\Index::class)->name('usuarios.index');

        // Módulo Costos y Precios
        Route::get('/costos/lotes',        Costos\Lotes::class)->name('costos.lotes');
        Route::get('/costos/valoracion',   Costos\Valoracion::class)->name('costos.valoracion');
        Route::get('/costos/rentabilidad', Costos\Rentabilidad::class)->name('costos.rentabilidad');

        // Categorías
        Route::get('/categorias', Categorias\Index::class)->name('categorias.index');

        // Reportes — Inventario
        Route::get('/reportes/inventario/productos',   [InventarioController::class, 'productos'])->name('reportes.inventario.productos');
        Route::get('/reportes/inventario/movimientos', [InventarioController::class, 'movimientos'])->name('reportes.inventario.movimientos');
        Route::get('/reportes/inventario/valoracion',  [InventarioController::class, 'valoracion'])->name('reportes.inventario.valoracion');

        // Reportes — Costos
        Route::get('/reportes/costos/lotes',       [CostosController::class, 'lotes'])->name('reportes.costos.lotes');
        Route::get('/reportes/costos/rentabilidad', [CostosController::class, 'rentabilidad'])->name('reportes.costos.rentabilidad');
    });
});

require __DIR__.'/settings.php';
