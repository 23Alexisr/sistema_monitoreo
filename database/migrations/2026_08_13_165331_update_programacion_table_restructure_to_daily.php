<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programacion', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'rol_obra', 'fecha_inicio', 'fecha_fin']);
        });

        Schema::table('programacion', function (Blueprint $table) {
            $table->foreignId('empleado_id')->after('obra_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha')->after('empleado_id');
            $table->time('hora')->nullable()->after('fecha');
            $table->enum('tipo', ['trabajo', 'viaje'])->default('trabajo')->after('hora');
            $table->boolean('es_encargado')->default(false)->after('tipo');
            $table->string('unidad')->nullable()->after('es_encargado');
            $table->string('placa')->nullable()->after('unidad');
        });
    }

    public function down(): void
    {
        Schema::table('programacion', function (Blueprint $table) {
            $table->dropForeign(['empleado_id']);
            $table->dropColumn(['empleado_id', 'fecha', 'hora', 'tipo', 'es_encargado', 'unidad', 'placa']);
        });

        Schema::table('programacion', function (Blueprint $table) {
            $table->foreignId('user_id')->after('obra_id')->constrained('users')->cascadeOnDelete();
            $table->string('rol_obra');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
        });
    }
};
