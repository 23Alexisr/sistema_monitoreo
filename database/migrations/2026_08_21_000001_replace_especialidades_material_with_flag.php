<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiales', function (Blueprint $table) {
            $table->boolean('requiere_especialidad_electricista')->default(false)->after('activo');
        });

        $materialIds = DB::table('especialidades_material')
            ->where('especialidad', 'electricista')
            ->pluck('material_id');

        DB::table('materiales')->whereIn('id', $materialIds)->update(['requiere_especialidad_electricista' => true]);

        Schema::dropIfExists('especialidades_material');
    }

    public function down(): void
    {
        Schema::create('especialidades_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales')->cascadeOnDelete();
            $table->string('especialidad');
            $table->timestamps();

            $table->unique(['material_id', 'especialidad']);
        });

        $materialIds = DB::table('materiales')->where('requiere_especialidad_electricista', true)->pluck('id');

        foreach ($materialIds as $materialId) {
            DB::table('especialidades_material')->insert([
                'material_id' => $materialId,
                'especialidad' => 'electricista',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('materiales', function (Blueprint $table) {
            $table->dropColumn('requiere_especialidad_electricista');
        });
    }
};
