<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterial extends EditRecord
{
    protected static string $resource = MaterialResource::class;

    protected ?array $especialidadesSeleccionadas = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['especialidades_permitidas'] = $this->record->especialidadesPermitidas->pluck('especialidad')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->especialidadesSeleccionadas = $data['especialidades_permitidas'] ?? [];
        unset($data['especialidades_permitidas']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->especialidadesPermitidas()->delete();

        foreach ($this->especialidadesSeleccionadas ?? [] as $especialidad) {
            $this->record->especialidadesPermitidas()->create(['especialidad' => $especialidad]);
        }
    }
}
