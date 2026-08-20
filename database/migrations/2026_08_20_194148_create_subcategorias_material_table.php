<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias_material')->cascadeOnDelete();
            $table->string('nombre');
            $table->integer('orden')->nullable();
            $table->string('prefijo', 6)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias_material');
    }
};
