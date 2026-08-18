<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->string('modelo')->nullable();
            $table->string('tipo')->nullable();
            $table->enum('estado', ['disponible', 'no_disponible'])->default('disponible');
            $table->enum('motivo_no_disponible', ['mantenimiento', 'falla', 'llantas', 'otro'])->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
