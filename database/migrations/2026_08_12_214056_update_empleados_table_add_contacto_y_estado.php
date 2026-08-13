<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('nombre_completo')->after('id');
            $table->string('telefono')->nullable()->after('dni');
            $table->string('estado')->default('activo')->after('especialidad');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn(['nombre_completo', 'telefono', 'estado']);
        });
    }
};
