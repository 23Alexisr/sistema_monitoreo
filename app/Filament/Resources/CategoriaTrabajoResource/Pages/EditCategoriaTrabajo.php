<?php

namespace App\Filament\Resources\CategoriaTrabajoResource\Pages;

use App\Filament\Resources\CategoriaTrabajoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoriaTrabajo extends EditRecord
{
    protected static string $resource = CategoriaTrabajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
