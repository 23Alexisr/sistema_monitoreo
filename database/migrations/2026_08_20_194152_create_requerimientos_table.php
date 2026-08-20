<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requerimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->nullable()->constrained('checklist_items')->nullOnDelete();
            $table->foreignId('requerimiento_original_id')->nullable()->constrained('requerimientos')->nullOnDelete();
            $table->foreignId('solicitado_por')->constrained('users')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('estado')->default('pendiente');
            $table->dateTime('fecha_solicitud');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('alistado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_alistamiento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimientos');
    }
};
