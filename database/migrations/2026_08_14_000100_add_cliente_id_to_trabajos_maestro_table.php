<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('categoria')->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
