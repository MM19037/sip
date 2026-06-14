<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos');
            $table->foreignId('usuario_id')->nullable()->constrained('users')
                ->comment('Operario de producción asignado');
            $table->enum('estado', ['asignado', 'en_proceso', 'completado', 'pausado'])
                ->default('asignado');
            $table->tinyInteger('prioridad')->default(2)->comment('1=Alta 2=Normal 3=Baja');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->integer('tiempo_minutos')
                ->storedAs('TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_fin)')
                ->nullable()
                ->comment('Tiempo real de producción en minutos');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('prioridad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_produccion');
    }
};
