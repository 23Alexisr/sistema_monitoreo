<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterial extends CreateRecord
{
    protected static string $resource = MaterialResource::class;

    protected ?array $especialidadesSeleccionadas = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->especialidadesSeleccionadas = $data['especialidades_permitidas'] ?? [];
        unset($data['especialidades_permitidas']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->especialidadesSeleccionadas ?? [] as $especialidad) {
            $this->record->especialidadesPermitidas()->create(['especialidad' => $especialidad]);
        }
    }
}
