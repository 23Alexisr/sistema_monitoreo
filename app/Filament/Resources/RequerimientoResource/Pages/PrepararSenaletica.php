<?php

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Filament\Resources\RequerimientoResource;
use App\Models\Requerimiento;
use App\Models\RequerimientoItem;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/**
 * Checklist de taller para el rol acabados: marcar cada item de un pedido
 * de señalética como preparado, uno por uno, sin el ruido de la vista de
 * detalle "de jefes" (ViewRequerimiento) que muestra todo el estado
 * administrativo del pedido (aprobación, auto-aprobado, etc).
 */
class PrepararSenaletica extends Page
{
    use InteractsWithRecord;

    protected static string $resource = RequerimientoResource::class;

    protected static string $view = 'filament.resources.requerimiento-resource.pages.preparar-senaletica';

    protected static ?string $title = 'Preparar señalética';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless($this->record->puedeGestionarAcabados(auth()->user()), 403);
    }

    public function getRequerimiento(): Requerimiento
    {
        /** @var Requerimiento */
        return $this->record;
    }

    public function getItems(): \Illuminate\Support\Collection
    {
        return $this->getRequerimiento()->items()->with('material')->get();
    }

    public function alternarPreparado(int $itemId): void
    {
        $item = RequerimientoItem::find($itemId);

        if (! $item || $item->requerimiento_id !== $this->record->id) {
            return;
        }

        if (! $this->record->puedeGestionarAcabados(auth()->user())) {
            return;
        }

        if ($item->preparado) {
            $item->desmarcarPreparado();
        } else {
            $item->marcarPreparado();
        }

        $this->record->refresh();
    }
}
