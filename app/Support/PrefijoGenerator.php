<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrefijoGenerator
{
    private const STOPWORDS = ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'en', 'para', 'con', 'a'];

    public static function generar(string $nombre): string
    {
        $base = static::palabraBase($nombre);
        $letras = static::letras($base, 3);

        if (! static::existe($letras)) {
            return $letras;
        }

        if (mb_strlen($base) > 3) {
            $conLetraExtra = static::letras($base, 4);

            if (! static::existe($conLetraExtra)) {
                return $conLetraExtra;
            }
        }

        for ($n = 2; $n < 100; $n++) {
            $candidato = $letras.$n;

            if (! static::existe($candidato)) {
                return $candidato;
            }
        }

        throw new \RuntimeException("No se pudo generar un prefijo único para \"{$nombre}\".");
    }

    private static function palabraBase(string $nombre): string
    {
        $palabras = preg_split('/\s+/', trim($nombre));
        $palabras = array_values(array_filter(
            $palabras,
            fn ($p) => $p !== '' && ! in_array(mb_strtolower($p), self::STOPWORDS, true)
        ));

        $palabra = $palabras[0] ?? trim($nombre);
        $palabra = Str::ascii($palabra);
        $palabra = preg_replace('/[^A-Za-z]/', '', $palabra);

        return $palabra !== '' ? $palabra : 'GEN';
    }

    private static function letras(string $base, int $largo): string
    {
        $letras = mb_strtoupper(mb_substr($base, 0, $largo));

        if (mb_strlen($letras) < $largo) {
            $relleno = mb_substr($letras, -1) ?: 'X';
            $letras = str_pad($letras, $largo, $relleno);
        }

        return $letras;
    }

    private static function existe(string $prefijo): bool
    {
        return DB::table('categorias_trabajo')->where('prefijo', $prefijo)->exists()
            || DB::table('subcategorias_trabajo')->where('prefijo', $prefijo)->exists()
            || DB::table('categorias_material')->where('prefijo', $prefijo)->exists()
            || DB::table('subcategorias_material')->where('prefijo', $prefijo)->exists();
    }
}
