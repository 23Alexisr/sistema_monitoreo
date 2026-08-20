<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_material', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('color')->nullable();
            $table->integer('orden')->nullable();
            $table->string('prefijo', 6)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_material');
    }
};
