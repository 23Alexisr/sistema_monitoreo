<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requerimiento_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requerimiento_id')->constrained('requerimientos')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materiales')->nullOnDelete();
            $table->string('descripcion_manual')->nullable();
            $table->string('medidas')->nullable();
            $table->string('foto_referencia')->nullable();
            $table->decimal('cantidad', 10, 2);
            $table->boolean('es_sugerido')->default(false);
            $table->boolean('preparado')->default(false);
            $table->boolean('verificado_despacho')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimiento_items');
    }
};
