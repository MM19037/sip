<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')
                ->comment('Asociado si el movimiento es por un pedido');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste']);
            $table->integer('cantidad')->comment('Positivo=entrada, negativo=salida');
            $table->decimal('costo_unitario', 10, 2)->default(0.00);
            $table->string('motivo', 200)->nullable()->comment('Compra, Uso en pedido, Ajuste, etc.');
            $table->timestamp('fecha')->useCurrent();

            $table->index('producto_id');
            $table->index('tipo');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
