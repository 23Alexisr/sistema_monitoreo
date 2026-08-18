<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programacion', function (Blueprint $table) {
            $table->boolean('es_conductor')->default(false)->after('vehiculo_id');
        });
    }

    public function down(): void
    {
        Schema::table('programacion', function (Blueprint $table) {
            $table->dropColumn('es_conductor');
        });
    }
};
