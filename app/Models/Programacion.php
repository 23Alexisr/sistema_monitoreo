<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Programacion extends Model
{
    use HasFactory;

    protected $table = 'programacion';

    protected $fillable = [
        'obra_id',
        'empleado_id',
        'fecha',
        'hora',
        'tipo',
        'es_encargado',
        'vehiculo_id',
        'es_conductor',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora' => 'datetime:H:i',
            'es_encargado' => 'boolean',
            'es_conductor' => 'boolean',
        ];
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public static function empleadoTieneOtraObraEseDia(int $empleadoId, string $fecha, ?int $obraId, ?int $ignorarId = null): bool
    {
        return static::query()
            ->where('empleado_id', $empleadoId)
            ->whereDate('fecha', $fecha)
            ->when($obraId, fn ($query) => $query->where('obra_id', '!=', $obraId))
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->exists();
    }

    public static function obraTieneEncargado(int $obraId, string $fecha, ?int $ignorarId = null): bool
    {
        return static::query()
            ->where('obra_id', $obraId)
            ->whereDate('fecha', $fecha)
            ->where('es_encargado', true)
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->exists();
    }

    public static function advertenciaSinEncargado(int $obraId, string $fecha): ?string
    {
        $hayPersonal = static::query()
            ->where('obra_id', $obraId)
            ->whereDate('fecha', $fecha)
            ->exists();

        if (! $hayPersonal || static::obraTieneEncargado($obraId, $fecha)) {
            return null;
        }

        return 'La obra no tiene ningún encargado asignado para el '.\Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y').'.';
    }

    public static function otrasAsignacionesEseDia(int $empleadoId, string $fecha): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('empleado_id', $empleadoId)
            ->whereDate('fecha', $fecha)
            ->with('obra')
            ->orderBy('orden')
            ->orderBy('hora')
            ->get();
    }
}
