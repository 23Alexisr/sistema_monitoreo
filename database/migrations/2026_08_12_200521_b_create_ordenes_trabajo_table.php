<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->unique()->constrained('obras')->cascadeOnDelete();
            $table->string('numero_ot')->unique();
            $table->text('alcance')->nullable();
            $table->text('especificaciones')->nullable();
            $table->string('estado')->default('pendiente');
            $table->date('fecha_emision')->nullable();
            $table->string('archivo_excel_original')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
