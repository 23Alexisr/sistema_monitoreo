<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales')->cascadeOnDelete();
            $table->string('especialidad');
            $table->timestamps();

            $table->unique(['material_id', 'especialidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades_material');
    }
};
