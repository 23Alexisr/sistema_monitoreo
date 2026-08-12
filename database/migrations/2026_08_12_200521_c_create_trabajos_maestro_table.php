<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajos_maestro', function (Blueprint $table) {
            $table->id();
            $table->string('categoria');
            $table->string('codigo')->unique();
            $table->string('descripcion');
            $table->unsignedInteger('dias_estimados')->default(1);
            $table->boolean('requiere_foto')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajos_maestro');
    }
};
