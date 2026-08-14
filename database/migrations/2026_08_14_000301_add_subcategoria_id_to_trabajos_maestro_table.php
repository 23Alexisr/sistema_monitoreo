<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->foreignId('subcategoria_id')->nullable()->after('categoria_id')->constrained('subcategorias_trabajo')->nullOnDelete();
        });

        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable(false)->change();
        });

        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategoria_id');
        });
    }
};
