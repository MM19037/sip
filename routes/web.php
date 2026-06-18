<?php

use App\Http\Controllers\Pedidos\DetalleController as PedidoDetalleController;
use App\Http\Controllers\Produccion\OrdenController as ProduccionOrdenController;
use App\Http\Controllers\Reportes\CostosController;
use App\Http\Controllers\Reportes\InventarioController;
use App\Http\Controllers\Reportes\PedidosController as ReportePedidosController;
use App\Http\Controllers\Reportes\ProduccionController as ReporteProduccionController;
use App\Livewire\Categorias;
use App\Livewire\Clientes;
use App\Livewire\Costos;
use App\Livewire\Dashboard;
use App\Livewire\Inventario;
use App\Livewire\Pedidos;
use App\Livewire\Permisos;
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

    // Pedidos
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:pedidos'])->group(function () {
        Route::get('/pedidos', Pedidos\Index::class)->name('pedidos.index');
        Route::get('/pedidos/crear', Pedidos\Crear::class)->name('pedidos.crear');
        Route::get('/pedidos/{pedido}', Pedidos\Ver::class)->name('pedidos.ver');
    });

    // Inventario
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:inventario'])->group(function () {
        Route::get('/inventario/productos', Inventario\Productos::class)->name('inventario.productos');
        Route::get('/inventario/movimientos', Inventario\Movimientos::class)->name('inventario.movimientos');
    });

    // Producción
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:produccion'])->group(function () {
        Route::get('/produccion', Produccion\Index::class)->name('produccion.index');
    });

    // Clientes
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:clientes'])->group(function () {
        Route::get('/clientes', Clientes\Index::class)->name('clientes.index');
    });

    // Solicitudes de stock — configurable por permisos
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:inventario.solicitudes'])->group(function () {
        Route::get('/inventario/solicitudes', Inventario\Solicitudes::class)->name('inventario.solicitudes');
    });

    // Costos y Precios — configurable por permisos
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:costos'])->group(function () {
        Route::get('/costos/lotes',        Costos\Lotes::class)->name('costos.lotes');
        Route::get('/costos/valoracion',   Costos\Valoracion::class)->name('costos.valoracion');
        Route::get('/costos/rentabilidad', Costos\Rentabilidad::class)->name('costos.rentabilidad');
    });

    // Categorías — configurable por permisos
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:categorias'])->group(function () {
        Route::get('/categorias', Categorias\Index::class)->name('categorias.index');
    });

    // PDF detalle de pedido — accesible con rol válido (pedidos o produccion)
    Route::middleware(['role:administrador,recepcionista,produccion'])->group(function () {
        Route::get('/pedidos/{pedido}/imprimir', [PedidoDetalleController::class, 'pdf'])->name('pedidos.imprimir');
        Route::get('/produccion/{orden}/imprimir', [ProduccionOrdenController::class, 'pdf'])->name('produccion.imprimir');
    });

    // Reporte lista de pedidos (PDF / CSV)
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:pedidos'])->group(function () {
        Route::get('/reportes/pedidos', [ReportePedidosController::class, 'index'])->name('reportes.pedidos');
    });

    // Reportes de inventario — mismo permiso que inventario
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:inventario'])->group(function () {
        Route::get('/reportes/inventario/productos',   [InventarioController::class, 'productos'])->name('reportes.inventario.productos');
        Route::get('/reportes/inventario/movimientos', [InventarioController::class, 'movimientos'])->name('reportes.inventario.movimientos');
        Route::get('/reportes/inventario/valoracion',  [InventarioController::class, 'valoracion'])->name('reportes.inventario.valoracion');
    });

    // Reportes de producción — mismo permiso que produccion
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:produccion'])->group(function () {
        Route::get('/reportes/produccion', [ReporteProduccionController::class, 'index'])->name('reportes.produccion');
    });

    // Reportes de costos — mismo permiso que costos
    Route::middleware(['role:administrador,recepcionista,produccion', 'permiso:costos'])->group(function () {
        Route::get('/reportes/costos/lotes',        [CostosController::class, 'lotes'])->name('reportes.costos.lotes');
        Route::get('/reportes/costos/rentabilidad', [CostosController::class, 'rentabilidad'])->name('reportes.costos.rentabilidad');
    });

    // Solo administrador
    Route::middleware(['role:administrador'])->group(function () {
        Route::get('/usuarios', Usuarios\Index::class)->name('usuarios.index');
        Route::get('/permisos', Permisos\Index::class)->name('permisos.index');
    });
});

require __DIR__.'/settings.php';
