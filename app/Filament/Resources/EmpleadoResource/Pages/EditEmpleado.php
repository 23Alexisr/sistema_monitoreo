<?php

namespace App\Filament\Resources\EmpleadoResource\Pages;

use App\Filament\Resources\EmpleadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user) {
            $data['email'] = $this->record->user->email;
            $data['rol'] = $this->record->user->roles->first()?->name;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return EmpleadoResource::procesarCuentaAcceso($data, $this->record->user_id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
