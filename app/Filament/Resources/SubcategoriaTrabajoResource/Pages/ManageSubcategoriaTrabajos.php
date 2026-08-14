<?php

namespace App\Filament\Resources\SubcategoriaTrabajoResource\Pages;

use App\Filament\Resources\SubcategoriaTrabajoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSubcategoriaTrabajos extends ManageRecords
{
    protected static string $resource = SubcategoriaTrabajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
