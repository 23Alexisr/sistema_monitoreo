<?php

namespace App\Filament\Resources\ProgramacionResource\Pages;

use App\Filament\Resources\ProgramacionResource;
use App\Models\Programacion;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListProgramacions extends Page
{
    protected static string $resource = ProgramacionResource::class;

    protected static string $view = 'filament.resources.programacion-resource.pages.list-programacions';

    public string $fecha;

    public function mount(): void
    {
        $fecha = request()->query('fecha');

        $this->fecha = $fecha && Carbon::hasFormat($fecha, 'Y-m-d') ? $fecha : now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('asignar')
                ->label('Asignar personal')
                ->icon('heroicon-o-user-plus')
                ->url(fn () => ProgramacionResource::getUrl('create', ['fecha' => $this->fecha]))
                ->visible(fn () => ProgramacionResource::canCreate()),
        ];
    }

    public function diaAnterior(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->toDateString();
    }

    public function diaSiguiente(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->toDateString();
    }

    public function irAHoy(): void
    {
        $this->fecha = now()->toDateString();
    }

    protected function getProgramacionesDelDia(): Collection
    {
        return Programacion::query()
            ->whereDate('fecha', $this->fecha)
            ->with(['obra.cliente', 'empleado.user.roles', 'vehiculo'])
            ->get();
    }

    public function getResumen(): array
    {
        $programaciones = $this->getProgramacionesDelDia();
        $porObra = $programaciones->groupBy('obra_id');

        return [
            'totalObras' => $porObra->count(),
            'totalPersonas' => $programaciones->count(),
            'obrasSinEncargado' => $porObra->filter(fn (Collection $registros) => ! $registros->contains('es_encargado', true))->count(),
        ];
    }

    public function getObrasDelDia(): Collection
    {
        return $this->getProgramacionesDelDia()
            ->groupBy('obra_id')
            ->map(function (Collection $registros) {
                $horaMin = $registros->pluck('hora')->filter()->sortBy(fn ($h) => $h->format('H:i:s'))->first();

                return (object) [
                    'obra' => $registros->first()->obra,
                    'registros' => $registros->sortBy(fn (Programacion $r) => $r->orden ?? 999)->values(),
                    'horaMin' => $horaMin,
                    'tieneViaje' => $registros->contains(fn (Programacion $r) => $r->tipo === 'viaje'),
                    'encargado' => $registros->first(fn (Programacion $r) => $r->es_encargado)?->empleado,
                    'vehiculo' => $registros->first(fn (Programacion $r) => $r->vehiculo_id)?->vehiculo,
                ];
            })
            ->sortBy(fn ($item) => $item->horaMin ? $item->horaMin->format('H:i:s') : '99:99:99')
            ->values();
    }
}
