<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('usuario_id')->constrained('users')->comment('Recepcionista que registró el pedido');
            $table->enum('estado', ['pendiente', 'en_produccion', 'listo', 'entregado', 'cancelado'])
                ->default('pendiente');
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('descuento', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->decimal('total_costo', 10, 2)->default(0.00)->comment('Costo total de producción');
            $table->decimal('ganancia', 10, 2)->storedAs('total - total_costo');
            $table->text('notas')->nullable();
            $table->timestamp('fecha_pedido')->useCurrent();
            $table->date('fecha_prometida')->nullable()->comment('Fecha de entrega acordada con el cliente');
            $table->timestamp('fecha_entrega')->nullable()->comment('Fecha real de entrega');
            $table->timestamps();

            $table->index('estado');
            $table->index('cliente_id');
            $table->index('fecha_pedido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
