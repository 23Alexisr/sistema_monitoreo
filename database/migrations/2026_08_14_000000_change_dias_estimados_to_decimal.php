<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->decimal('dias_estimados', 5, 2)->default(1)->change();
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->decimal('dias_estimados_override', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->unsignedInteger('dias_estimados')->default(1)->change();
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->unsignedInteger('dias_estimados_override')->nullable()->change();
        });
    }
};
