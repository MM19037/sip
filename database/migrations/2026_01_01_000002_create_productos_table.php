<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('categoria', 80)->comment('Tazas, Camisetas, Lapiceros, Viniles, etc.');
            $table->text('descripcion')->nullable();
            $table->decimal('costo_base', 10, 2)->default(0.00);
            $table->decimal('margen_ganancia', 5, 2)->default(0.00)->comment('Porcentaje de margen (%)');
            $table->decimal('precio_venta', 10, 2)
                ->storedAs('ROUND(costo_base * (1 + margen_ganancia / 100), 2)')
                ->comment('Calculado: costo_base * (1 + margen%)');
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(5)->comment('Umbral para alerta de stock bajo');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('categoria');
            $table->index(['stock_actual', 'stock_minimo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
