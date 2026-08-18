<?php

namespace App\Filament\Resources\ProgramacionResource\Pages;

use App\Filament\Resources\ProgramacionResource;
use App\Models\Programacion;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProgramacion extends EditRecord
{
    protected static string $resource = ProgramacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($mensaje = Programacion::advertenciaSinEncargado($record->obra_id, $record->fecha->toDateString())) {
            Notification::make()
                ->warning()
                ->title('Posible vacío de liderazgo')
                ->body($mensaje)
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->getRecord();

        return ProgramacionResource::getUrl('detalle', [
            'obra' => $record->obra_id,
            'fecha' => $record->fecha->toDateString(),
        ]);
    }
}
