<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documentos MODIFY tipo ENUM('plano', 'fotomontaje', 'foto_avance', 'otro') NOT NULL DEFAULT 'otro'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE documentos MODIFY tipo VARCHAR(255) NOT NULL');
    }
};
