<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_material', function (Blueprint $table) {
            $table->dropColumn('requiere_especialidad_electricista');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_material', function (Blueprint $table) {
            $table->boolean('requiere_especialidad_electricista')->default(false)->after('prefijo');
        });
    }
};
