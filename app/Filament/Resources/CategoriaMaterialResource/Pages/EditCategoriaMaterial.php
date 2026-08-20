<?php

namespace App\Filament\Resources\CategoriaMaterialResource\Pages;

use App\Filament\Resources\CategoriaMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoriaMaterial extends EditRecord
{
    protected static string $resource = CategoriaMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
