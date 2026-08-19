<?php

namespace App\Models;

use App\Enums\EstadoChecklistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'trabajo_maestro_id',
        'parent_id',
        'descripcion',
        'dias_estimados_override',
        'orden',
        'estado',
        'requiere_foto',
        'observaciones',
        'aprobado_por',
        'fecha_aprobacion',
        'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return [
            'dias_estimados_override' => 'decimal:2',
            'estado' => EstadoChecklistItem::class,
            'requiere_foto' => 'boolean',
            'fecha_aprobacion' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChecklistItem $item) {
            if ($item->trabajo_maestro_id && ! $item->descripcion) {
                $maestro = TrabajoMaestro::find($item->trabajo_maestro_id);

                if ($maestro) {
                    $item->descripcion = $maestro->descripcion;
                    $item->requiere_foto = $maestro->requiere_foto;

                    if ($item->dias_estimados_override === null) {
                        $item->dias_estimados_override = $maestro->dias_estimados;
                    }
                }
            }

            if (blank($item->orden)) {
                $item->orden = static::hermanosDelGrupo($item->checklist_id, $item->parent_id)->max('orden') + 1;
            }
        });

        static::saving(function (ChecklistItem $item) {
            if ($item->orden !== null && $item->orden < 1) {
                throw new \RuntimeException('El campo orden debe ser un entero mayor a 0.');
            }
        });
    }

    protected static function hermanosDelGrupo(int $checklistId, ?int $parentId): Builder
    {
        $query = static::where('checklist_id', $checklistId);

        return $parentId ? $query->where('parent_id', $parentId) : $query->whereNull('parent_id');
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function trabajoMaestro(): BelongsTo
    {
        return $this->belongsTo(TrabajoMaestro::class, 'trabajo_maestro_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'parent_id')->orderBy('orden');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class);
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function dias(): float
    {
        return (float) ($this->dias_estimados_override ?? $this->trabajoMaestro?->dias_estimados ?? 0);
    }

    public function categoriaEfectiva(): ?CategoriaTrabajo
    {
        return $this->trabajoMaestro?->categoriaEfectiva();
    }

    public function tieneFotoAntes(): bool
    {
        return $this->fotos()->where('momento', 'antes')->exists();
    }

    public function tieneFotoDespues(): bool
    {
        return $this->fotos()->where('momento', 'despues')->exists();
    }

    /**
     * Aprobar/rechazar solo aplica dentro del ciclo de evidencia fotográfica
     * (pendiente → pendiente_aprobacion → completado/rechazado). Una vez
     * completado, no se toca automáticamente por cambios en las fotos.
     */
    public function sincronizarEstadoAutomatico(): void
    {
        if (! $this->requiere_foto) {
            return;
        }

        if (! in_array($this->estado, [EstadoChecklistItem::Pendiente, EstadoChecklistItem::PendienteAprobacion, EstadoChecklistItem::Rechazado], true)) {
            return;
        }

        $tieneAmbas = $this->tieneFotoAntes() && $this->tieneFotoDespues();

        if ($tieneAmbas && $this->estado !== EstadoChecklistItem::PendienteAprobacion) {
            $this->update([
                'estado' => EstadoChecklistItem::PendienteAprobacion,
                'motivo_rechazo' => null,
            ]);

            return;
        }

        if (! $tieneAmbas && $this->estado === EstadoChecklistItem::PendienteAprobacion) {
            $this->update(['estado' => EstadoChecklistItem::Pendiente]);
        }
    }

    public function aprobar(User $usuario): void
    {
        $this->update([
            'estado' => EstadoChecklistItem::Completado,
            'aprobado_por' => $usuario->id,
            'fecha_aprobacion' => now(),
            'motivo_rechazo' => null,
        ]);
    }

    public function rechazar(string $motivo): void
    {
        $this->update([
            'estado' => EstadoChecklistItem::Rechazado,
            'motivo_rechazo' => $motivo,
            'aprobado_por' => null,
            'fecha_aprobacion' => null,
        ]);
    }
}
