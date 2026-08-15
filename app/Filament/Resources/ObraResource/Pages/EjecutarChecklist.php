<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Filament\Resources\ObraResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Foto;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class EjecutarChecklist extends Page
{
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ObraResource::class;

    protected static string $view = 'filament.resources.obra-resource.pages.ejecutar-checklist';

    protected static ?string $title = 'Ejecutar checklist';

    protected static ?string $navigationLabel = 'Ejecutar checklist';

    public ?int $itemSeleccionadoId = null;

    public ?string $observacionesTexto = null;

    public $fotoAntes = null;

    public $fotoDespues = null;

    public function mount(int | string $record): void
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
     */
    public function getSeccionesAgrupadas(): Collection
    {
        $checklist = $this->getChecklist();

        if (! $checklist) {
            return new Collection();
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
                    'pendientes' => $grupo->where('completado', false)->sortBy('orden')->values(),
                    'completados' => $grupo->where('completado', true)->sortBy('orden')->values(),
                    'total' => $grupo->count(),
                    'completadosCount' => $grupo->where('completado', true)->count(),
                ];
            })
            ->sortBy('orden')
            ->values();
    }

    public function getItemSeleccionado(): ?ChecklistItem
    {
        if (! $this->itemSeleccionadoId) {
            return null;
        }

        return ChecklistItem::with('fotos')->find($this->itemSeleccionadoId);
    }

    public function seleccionarItem(int $itemId): void
    {
        $this->itemSeleccionadoId = $itemId;
        $this->observacionesTexto = ChecklistItem::find($itemId)?->observaciones;
        $this->fotoAntes = null;
        $this->fotoDespues = null;
    }

    public function volverALista(): void
    {
        $this->itemSeleccionadoId = null;
        $this->fotoAntes = null;
        $this->fotoDespues = null;
    }

    public function alternarCompletado(): void
    {
        $item = $this->getItemSeleccionado();

        if (! $item || $item->requiere_foto) {
            return;
        }

        $item->update(['completado' => ! $item->completado]);
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
        $this->procesarFoto('antes');
    }

    public function updatedFotoDespues(): void
    {
        $this->procesarFoto('despues');
    }

    protected function procesarFoto(string $momento): void
    {
        $item = $this->getItemSeleccionado();
        $archivo = $momento === 'antes' ? $this->fotoAntes : $this->fotoDespues;

        if (! $item || ! $item->requiere_foto || ! $archivo) {
            return;
        }

        $path = $archivo->store('checklist-fotos', 'public');

        Foto::create([
            'checklist_item_id' => $item->id,
            'momento' => $momento,
            'url' => $path,
            'subido_por' => Auth::id(),
            'fecha_subida' => now(),
        ]);

        if ($momento === 'antes') {
            $this->fotoAntes = null;
        } else {
            $this->fotoDespues = null;
        }

        Notification::make()->success()->title('Foto subida')->send();
    }
}
