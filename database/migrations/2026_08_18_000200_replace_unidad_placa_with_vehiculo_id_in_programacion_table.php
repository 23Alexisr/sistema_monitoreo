<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programacion', function (Blueprint $table) {
            $table->dropColumn(['unidad', 'placa']);
            $table->foreignId('vehiculo_id')->nullable()->after('es_encargado')->constrained('vehiculos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programacion', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropColumn('vehiculo_id');
            $table->string('unidad')->nullable()->after('es_encargado');
            $table->string('placa')->nullable()->after('unidad');
        });
    }
};
