<?php

namespace App\Filament\Resources\TrabajoMaestroResource\Pages;

use App\Filament\Resources\TrabajoMaestroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrabajoMaestro extends EditRecord
{
    protected static string $resource = TrabajoMaestroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
