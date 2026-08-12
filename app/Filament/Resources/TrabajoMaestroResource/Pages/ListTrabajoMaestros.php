<?php

namespace App\Filament\Resources\TrabajoMaestroResource\Pages;

use App\Filament\Resources\TrabajoMaestroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrabajoMaestros extends ListRecords
{
    protected static string $resource = TrabajoMaestroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
