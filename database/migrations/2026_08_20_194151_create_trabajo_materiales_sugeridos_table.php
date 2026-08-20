<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajo_materiales_sugeridos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajo_maestro_id')->constrained('trabajos_maestro')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales')->cascadeOnDelete();
            $table->decimal('cantidad_sugerida', 10, 2);
            $table->timestamps();

            $table->unique(['trabajo_maestro_id', 'material_id'], 'trabajo_material_sugerido_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajo_materiales_sugeridos');
    }
};
