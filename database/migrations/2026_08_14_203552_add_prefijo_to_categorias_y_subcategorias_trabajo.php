<?php

use App\Support\PrefijoGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_trabajo', function (Blueprint $table) {
            $table->string('prefijo', 6)->nullable()->unique()->after('nombre');
        });

        Schema::table('subcategorias_trabajo', function (Blueprint $table) {
            $table->string('prefijo', 6)->nullable()->unique()->after('nombre');
        });

        foreach (DB::table('categorias_trabajo')->orderBy('id')->get() as $categoria) {
            DB::table('categorias_trabajo')->where('id', $categoria->id)->update([
                'prefijo' => PrefijoGenerator::generar($categoria->nombre),
            ]);
        }

        foreach (DB::table('subcategorias_trabajo')->orderBy('id')->get() as $subcategoria) {
            DB::table('subcategorias_trabajo')->where('id', $subcategoria->id)->update([
                'prefijo' => PrefijoGenerator::generar($subcategoria->nombre),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('categorias_trabajo', function (Blueprint $table) {
            $table->dropColumn('prefijo');
        });

        Schema::table('subcategorias_trabajo', function (Blueprint $table) {
            $table->dropColumn('prefijo');
        });
    }
};
