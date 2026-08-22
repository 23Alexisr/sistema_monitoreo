<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requerimiento_items', function (Blueprint $table) {
            $table->decimal('ancho_pedido', 10, 2)->nullable()->after('medidas');
            $table->decimal('largo_pedido', 10, 2)->nullable()->after('ancho_pedido');
        });
    }

    public function down(): void
    {
        Schema::table('requerimiento_items', function (Blueprint $table) {
            $table->dropColumn(['ancho_pedido', 'largo_pedido']);
        });
    }
};
