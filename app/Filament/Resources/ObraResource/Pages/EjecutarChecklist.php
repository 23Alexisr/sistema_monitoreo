<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Filament\Resources\ObraResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Foto;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
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

    public function getItems(): Collection
    {
        return $this->getChecklist()
            ?->items()
            ->with(['children.fotos', 'fotos'])
            ->get() ?? new Collection();
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
