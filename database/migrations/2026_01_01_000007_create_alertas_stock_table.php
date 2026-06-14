<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('stock_al_generar');
            $table->integer('stock_minimo');
            $table->boolean('resuelta')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('resuelta_at')->nullable();

            $table->index('resuelta');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_stock');
    }
};
