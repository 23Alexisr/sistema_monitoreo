<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('categoria')->constrained('categorias_trabajo');
        });

        $categorias = DB::table('trabajos_maestro')
            ->whereNotNull('categoria')
            ->pluck('categoria')
            ->groupBy(fn ($valor) => mb_strtolower(trim($valor)));

        foreach ($categorias as $clave => $valores) {
            $categoriaId = DB::table('categorias_trabajo')->insertGetId([
                'nombre' => trim($valores->first()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('trabajos_maestro')
                ->whereRaw('LOWER(TRIM(categoria)) = ?', [$clave])
                ->update(['categoria_id' => $categoriaId]);
        }

        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });

        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('categoria_id');
        });

        DB::table('trabajos_maestro')
            ->join('categorias_trabajo', 'trabajos_maestro.categoria_id', '=', 'categorias_trabajo.id')
            ->update(['trabajos_maestro.categoria' => DB::raw('categorias_trabajo.nombre')]);

        Schema::table('trabajos_maestro', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
        });
    }
};
