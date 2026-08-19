<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'pendiente_aprobacion', 'completado', 'rechazado'])
                ->default('pendiente')
                ->after('requiere_foto');

            $table->foreignId('aprobado_por')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_aprobacion')->nullable()->after('aprobado_por');
            $table->text('motivo_rechazo')->nullable()->after('fecha_aprobacion');
        });

        DB::table('checklist_items')->where('completado', true)->update(['estado' => 'completado']);

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('completado');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->boolean('completado')->default(false)->after('requiere_foto');
        });

        DB::table('checklist_items')->where('estado', 'completado')->update(['completado' => true]);

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn(['estado', 'fecha_aprobacion', 'motivo_rechazo']);
        });
    }
};
