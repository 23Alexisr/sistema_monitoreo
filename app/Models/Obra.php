<?php

namespace App\Models;

use App\Enums\EstadoChecklistItem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Obra extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'codigo',
        'nombre',
        'ubicacion',
        'link_maps',
        'estado',
        'fecha_inicio',
        'fecha_fin_estimada',
    ];

    protected static function booted(): void
    {
        static::creating(function (Obra $obra) {
            if (filled($obra->codigo)) {
                return;
            }

            $ultimoCodigo = static::query()
                ->where('codigo', 'like', 'OBR-%')
                ->orderByRaw('CAST(SUBSTRING(codigo, 5) AS UNSIGNED) DESC')
                ->value('codigo');

            $siguiente = $ultimoCodigo ? ((int) substr($ultimoCodigo, 4)) + 1 : 1;

            $obra->codigo = 'OBR-'.str_pad((string) $siguiente, 7, '0', STR_PAD_LEFT);
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin_estimada' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordenTrabajo(): HasOne
    {
        return $this->hasOne(OrdenTrabajo::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function programaciones(): HasMany
    {
        return $this->hasMany(Programacion::class);
    }

    public function requerimientos(): HasMany
    {
        return $this->hasMany(Requerimiento::class);
    }

    public function jefesProyecto(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'obra_jefe_proyecto', 'obra_id', 'jefe_proyecto_id');
    }

    public function asignadaAOperario(User $user): bool
    {
        return $this->programaciones()
            ->whereHas('empleado', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function personalHoy(): Collection
    {
        return Empleado::query()
            ->whereHas('programaciones', fn ($q) => $q
                ->where('obra_id', $this->id)
                ->whereDate('fecha', now()->toDateString()))
            ->with('user.roles')
            ->get();
    }

    protected function itemsParaAvance(): Collection
    {
        $checklist = $this->ordenTrabajo?->checklist;

        if (! $checklist) {
            return collect();
        }

        return $checklist->items()
            ->whereNull('parent_id')
            ->get()
            ->flatMap(fn (ChecklistItem $item) => $item->children->isNotEmpty() ? $item->children : collect([$item]));
    }

    public function calcularAvance(): float
    {
        $items = $this->itemsParaAvance();

        $diasTotales = $items->sum(fn (ChecklistItem $item) => $item->dias());

        if ($diasTotales <= 0) {
            return 0.0;
        }

        $diasCompletados = $items->where('estado', EstadoChecklistItem::Completado)->sum(fn (ChecklistItem $item) => $item->dias());

        return round(($diasCompletados / $diasTotales) * 100, 2);
    }

    public function resumenChecklist(): array
    {
        $items = $this->itemsParaAvance();

        return [
            'listos' => $items->where('estado', EstadoChecklistItem::Completado)->count(),
            'pendientes' => $items->where('estado', '!=', EstadoChecklistItem::Completado)->count(),
        ];
    }

    /**
     * Items que requieren acción del supervisor: pendientes o rechazados.
     * Los que están en pendiente_aprobacion ya no dependen de él, están
     * esperando revisión de jefatura.
     */
    public function checklistPendientes(): Collection
    {
        return $this->itemsParaAvance()
            ->whereIn('estado', [EstadoChecklistItem::Pendiente, EstadoChecklistItem::Rechazado])
            ->sortBy('orden')
            ->values();
    }

    protected function avancePct(): Attribute
    {
        return Attribute::make(get: fn () => $this->calcularAvance());
    }
}
