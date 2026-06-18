<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_rol', function (Blueprint $table) {
            $table->id();
            $table->enum('rol', ['administrador', 'recepcionista', 'produccion']);
            $table->string('seccion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['rol', 'seccion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_rol');
    }
};
