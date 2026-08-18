<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('empleado_responsable_id')->nullable()->after('tipo')->constrained('empleados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['empleado_responsable_id']);
            $table->dropColumn('empleado_responsable_id');
        });
    }
};
