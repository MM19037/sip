<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2)->comment('Precio al momento del pedido');
            $table->decimal('costo_unitario', 10, 2)->comment('Costo al momento del pedido');
            $table->decimal('subtotal', 10, 2)->storedAs('cantidad * precio_unitario');
            $table->text('descripcion_custom')->nullable()->comment('Personalización: color, texto, diseño');

            $table->index('pedido_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedido');
    }
};
