<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Filament\Resources\ObraResource;
use App\Models\Obra;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ListObras extends Page
{
    protected static string $resource = ObraResource::class;

    protected static string $view = 'filament.resources.obra-resource.pages.list-obras';

    public string $filtro = 'activas';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear')
                ->label('Agregar obra')
                ->icon('heroicon-o-plus')
                ->url(fn () => static::getResource()::getUrl('create'))
                ->visible(fn () => static::getResource()::canCreate()),
        ];
    }

    public function setFiltro(string $filtro): void
    {
        $this->filtro = $filtro;
    }

    public function getObras(): Collection
    {
        $query = ObraResource::getEloquentQuery()
            ->with(['cliente', 'ordenTrabajo.checklist.items.children']);

        if ($this->filtro === 'activas') {
            $query->whereNotIn('estado', ['finalizada', 'cancelada']);
        }

        return $query->get()
            ->sortBy(fn (Obra $obra) => $obra->avance_pct)
            ->values();
    }
}
