<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_material', function (Blueprint $table) {
            $table->boolean('requiere_especialidad_electricista')->default(false)->after('prefijo');
        });

        $categoriaIdsDirectas = DB::table('materiales')
            ->where('requiere_especialidad_electricista', true)
            ->whereNotNull('categoria_id')
            ->pluck('categoria_id');

        $subcategoriaIds = DB::table('materiales')
            ->where('requiere_especialidad_electricista', true)
            ->whereNotNull('subcategoria_id')
            ->pluck('subcategoria_id');

        $categoriaIdsViaSubcategoria = DB::table('subcategorias_material')
            ->whereIn('id', $subcategoriaIds)
            ->pluck('categoria_id');

        $categoriaIds = $categoriaIdsDirectas->merge($categoriaIdsViaSubcategoria)->unique();

        DB::table('categorias_material')->whereIn('id', $categoriaIds)->update(['requiere_especialidad_electricista' => true]);

        Schema::table('materiales', function (Blueprint $table) {
            $table->dropColumn('requiere_especialidad_electricista');
        });
    }

    public function down(): void
    {
        Schema::table('materiales', function (Blueprint $table) {
            $table->boolean('requiere_especialidad_electricista')->default(false)->after('activo');
        });

        $categoriaIds = DB::table('categorias_material')
            ->where('requiere_especialidad_electricista', true)
            ->pluck('id');

        $subcategoriaIds = DB::table('subcategorias_material')->whereIn('categoria_id', $categoriaIds)->pluck('id');

        DB::table('materiales')->whereIn('categoria_id', $categoriaIds)->update(['requiere_especialidad_electricista' => true]);
        DB::table('materiales')->whereIn('subcategoria_id', $subcategoriaIds)->update(['requiere_especialidad_electricista' => true]);

        Schema::table('categorias_material', function (Blueprint $table) {
            $table->dropColumn('requiere_especialidad_electricista');
        });
    }
};
