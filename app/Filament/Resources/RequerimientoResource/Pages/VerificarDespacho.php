<?php

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Filament\Resources\RequerimientoResource;
use App\Models\Requerimiento;
use App\Models\RequerimientoItem;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/**
 * Checklist de despacho: segunda validación de cada item de un pedido de
 * señalética ya preparado por acabados, antes de que quede entregado.
 * Mismo criterio que PrepararSenaletica — pantalla de tarea, no la vista
 * de detalle administrativa.
 */
class VerificarDespacho extends Page
{
    use InteractsWithRecord;

    protected static string $resource = RequerimientoResource::class;

    protected static string $view = 'filament.resources.requerimiento-resource.pages.verificar-despacho';

    protected static ?string $title = 'Verificar despacho';

    public ?int $itemParaRechazarId = null;

    public ?string $motivoRechazoTexto = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless($this->record->puedeGestionarDespacho(auth()->user()), 403);
    }

    public function getRequerimiento(): Requerimiento
    {
        /** @var Requerimiento */
        return $this->record;
    }

    public function getItems(): \Illuminate\Support\Collection
    {
        return $this->getRequerimiento()->items()->with('material', 'verificadoPor.empleado')->get();
    }

    public function verificarItem(int $itemId): void
    {
        $item = RequerimientoItem::find($itemId);

        if (! $item || $item->requerimiento_id !== $this->record->id) {
            return;
        }

        if (! $this->record->puedeGestionarDespacho(auth()->user()) || ! $item->preparado) {
            return;
        }

        $item->verificarDespacho(auth()->user());
        $this->record->refresh();
    }

    public function abrirRechazo(int $itemId): void
    {
        $this->itemParaRechazarId = $itemId;
        $this->motivoRechazoTexto = null;
    }

    public function cancelarRechazo(): void
    {
        $this->itemParaRechazarId = null;
        $this->motivoRechazoTexto = null;
    }

    public function confirmarRechazo(): void
    {
        if (! $this->itemParaRechazarId) {
            return;
        }

        if (blank($this->motivoRechazoTexto)) {
            Notification::make()->danger()->title('Indica qué está mal')->send();

            return;
        }

        $item = RequerimientoItem::find($this->itemParaRechazarId);

        if ($item && $item->requerimiento_id === $this->record->id && $this->record->puedeGestionarDespacho(auth()->user())) {
            $item->rechazarDespacho($this->motivoRechazoTexto);
            $this->record->refresh();
        }

        $this->itemParaRechazarId = null;
        $this->motivoRechazoTexto = null;
    }
}
