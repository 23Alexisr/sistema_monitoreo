<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requerimiento_items', function (Blueprint $table) {
            $table->foreignId('verificado_por')->nullable()->after('verificado_despacho')->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_verificacion')->nullable()->after('verificado_por');
        });
    }

    public function down(): void
    {
        Schema::table('requerimiento_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verificado_por');
            $table->dropColumn('fecha_verificacion');
        });
    }
};
