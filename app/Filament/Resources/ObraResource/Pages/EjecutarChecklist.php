<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Enums\EstadoChecklistItem;
use App\Filament\Resources\ObraResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Foto;
use App\Models\Obra;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class EjecutarChecklist extends Page
{
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ObraResource::class;

    protected static string $view = 'filament.resources.obra-resource.pages.ejecutar-checklist';

    protected static ?string $title = 'Checklist';

    protected static ?string $navigationLabel = 'Checklist';

    public ?int $itemSeleccionadoId = null;

    public string $filtro = 'pendientes';

    public ?int $fotoAmpliadaId = null;

    public ?string $observacionesTexto = null;

    public ?string $motivoRechazoTexto = null;

    public array $fotoAntes = [];

    public array $fotoDespues = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return ! (auth()->user()?->hasRole('operario') ?? true);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canAccess(['record' => $this->record]), 403);
    }

    public function getChecklist(): ?Checklist
    {
        return $this->record->ordenTrabajo?->checklist;
    }

    /**
     * Todos los items del checklist (padres e hijos por igual, sin excluir
     * ninguno vía relaciones anidadas) agrupados por la categoría efectiva
     * de su trabajo_maestro (directa o vía subcategoría). Los items
     * manuales (sin trabajo_maestro_id) caen en la sección "Otros".
     *
     * Cada grupo se divide en 3 baldes según estado: pendientes (incluye
     * rechazados, ya que requieren la misma acción del supervisor),
     * enRevision (esperando aprobación de jefatura) y completados.
     */
    public function getSeccionesAgrupadas(): Collection
    {
        $checklist = $this->getChecklist();

        if (! $checklist) {
            return new Collection;
        }

        $items = ChecklistItem::query()
            ->where('checklist_id', $checklist->id)
            ->with(['fotos', 'trabajoMaestro.categoria', 'trabajoMaestro.subcategoria.categoria'])
            ->get();

        return $items
            ->groupBy(fn (ChecklistItem $item) => $item->categoriaEfectiva()?->id ?? 'otros')
            ->map(function (Collection $grupo) {
                $categoria = $grupo->first()->categoriaEfectiva();

                return [
                    'nombre' => $categoria?->nombre ?? 'Otros / Items manuales',
                    'color' => $categoria?->color,
                    'orden' => $categoria?->orden ?? PHP_INT_MAX,
                    'pendientes' => $grupo->whereIn('estado', [EstadoChecklistItem::Pendiente, EstadoChecklistItem::Rechazado])->sortBy('orden')->values(),
                    'enRevision' => $grupo->where('estado', EstadoChecklistItem::PendienteAprobacion)->sortBy('orden')->values(),
                    'completados' => $grupo->where('estado', EstadoChecklistItem::Completado)->sortBy('orden')->values(),
                    'total' => $grupo->count(),
                    'completadosCount' => $grupo->where('estado', EstadoChecklistItem::Completado)->count(),
                ];
            })
            ->sortBy('orden')
            ->values();
    }

    public function setFiltro(string $filtro): void
    {
        $this->filtro = $filtro;
    }

    public function getSiguientePendiente(): ?ChecklistItem
    {
        /** @var Obra $obra */
        $obra = $this->record;

        return $obra->checklistPendientes()->first();
    }

    public function puedeAprobar(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'jefe_cuadrilla']) ?? false;
    }

    public function getItemSeleccionado(): ?ChecklistItem
    {
        if (! $this->itemSeleccionadoId) {
            return null;
        }

        return ChecklistItem::with('fotos', 'aprobadoPor')->find($this->itemSeleccionadoId);
    }

    public function seleccionarItem(int $itemId): void
    {
        $this->itemSeleccionadoId = $itemId;
        $this->observacionesTexto = ChecklistItem::find($itemId)?->observaciones;
        $this->motivoRechazoTexto = null;
        $this->fotoAntes = [];
        $this->fotoDespues = [];
    }

    public function volverALista(): void
    {
        $this->itemSeleccionadoId = null;
        $this->motivoRechazoTexto = null;
        $this->fotoAntes = [];
        $this->fotoDespues = [];
    }

    public function getFotoAmpliada(): ?Foto
    {
        return $this->fotoAmpliadaId ? Foto::find($this->fotoAmpliadaId) : null;
    }

    public function ampliarFoto(int $fotoId): void
    {
        $this->fotoAmpliadaId = $fotoId;
    }

    public function cerrarFotoAmpliada(): void
    {
        $this->fotoAmpliadaId = null;
    }

    public function eliminarFotoAmpliada(): void
    {
        $foto = $this->getFotoAmpliada();

        if (! $foto) {
            return;
        }

        Storage::disk('public')->delete($foto->url);
        $foto->delete();

        $this->fotoAmpliadaId = null;

        Notification::make()->success()->title('Foto eliminada')->send();
    }

    public function alternarCompletado(): void
    {
        $item = $this->getItemSeleccionado();

        if (! $item || $item->requiere_foto) {
            return;
        }

        $item->update([
            'estado' => $item->estado === EstadoChecklistItem::Completado
                ? EstadoChecklistItem::Pendiente
                : EstadoChecklistItem::Completado,
        ]);
    }

    public function aprobarItem(): void
    {
        $item = $this->getItemSeleccionado();

        if (! $item || ! $this->puedeAprobar() || $item->estado !== EstadoChecklistItem::PendienteAprobacion) {
            return;
        }

        $item->aprobar(Auth::user());

        Notification::make()->success()->title('Item aprobado')->send();
    }

    public function rechazarItem(): void
    {
        $item = $this->getItemSeleccionado();

        if (! $item || ! $this->puedeAprobar() || $item->estado !== EstadoChecklistItem::PendienteAprobacion) {
            return;
        }

        if (blank($this->motivoRechazoTexto)) {
            Notification::make()->danger()->title('Hace falta indicar el motivo del rechazo')->send();

            return;
        }

        $item->rechazar($this->motivoRechazoTexto);
        $this->motivoRechazoTexto = null;

        Notification::make()->danger()->title('Item rechazado')->send();
    }

    public function guardarObservaciones(): void
    {
        $item = $this->getItemSeleccionado();

        if (! $item) {
            return;
        }

        $item->update(['observaciones' => $this->observacionesTexto]);

        Notification::make()->success()->title('Observación guardada')->send();
    }

    public function updatedFotoAntes(): void
    {
        $this->procesarFotos('antes');
    }

    public function updatedFotoDespues(): void
    {
        $this->procesarFotos('despues');
    }

    protected function procesarFotos(string $momento): void
    {
        $item = $this->getItemSeleccionado();
        $archivos = $momento === 'antes' ? $this->fotoAntes : $this->fotoDespues;

        if (! $item || ! $item->requiere_foto || $item->estado === EstadoChecklistItem::Completado || empty($archivos)) {
            return;
        }

        foreach ($archivos as $archivo) {
            $path = $archivo->store('checklist-fotos', 'public');

            Foto::create([
                'checklist_item_id' => $item->id,
                'momento' => $momento,
                'url' => $path,
                'subido_por' => Auth::id(),
                'fecha_subida' => now(),
            ]);
        }

        if ($momento === 'antes') {
            $this->fotoAntes = [];
        } else {
            $this->fotoDespues = [];
        }

        Notification::make()->success()->title(count($archivos) > 1 ? 'Fotos subidas' : 'Foto subida')->send();
    }
}
