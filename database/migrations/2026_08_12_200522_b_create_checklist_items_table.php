<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('checklists')->cascadeOnDelete();
            $table->foreignId('trabajo_maestro_id')->nullable()->constrained('trabajos_maestro')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('checklist_items')->cascadeOnDelete();
            $table->string('descripcion');
            $table->unsignedInteger('dias_estimados_override')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('completado')->default(false);
            $table->boolean('requiere_foto')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
