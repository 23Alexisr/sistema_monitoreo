<?php

namespace App\Filament\Resources\CategoriaMaterialResource\Pages;

use App\Filament\Resources\CategoriaMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoriaMaterials extends ListRecords
{
    protected static string $resource = CategoriaMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
