<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicados = DB::table('categorias_trabajo')
            ->select('orden')
            ->groupBy('orden')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('orden');

        foreach ($duplicados as $orden) {
            $filas = DB::table('categorias_trabajo')->where('orden', $orden)->orderBy('id')->get();

            foreach ($filas->skip(1) as $fila) {
                $siguiente = DB::table('categorias_trabajo')->max('orden') + 1;

                DB::table('categorias_trabajo')->where('id', $fila->id)->update(['orden' => $siguiente]);
            }
        }
    }

    public function down(): void
    {
        // Corrección de datos: no reversible de forma significativa.
    }
};
