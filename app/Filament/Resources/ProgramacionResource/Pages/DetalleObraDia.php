<?php

namespace App\Filament\Resources\ProgramacionResource\Pages;

use App\Filament\Resources\ProgramacionResource;
use App\Models\Obra;
use App\Models\Programacion;
use App\Models\Vehiculo;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DetalleObraDia extends Page
{
    protected static string $resource = ProgramacionResource::class;

    protected static string $view = 'filament.resources.programacion-resource.pages.detalle-obra-dia';

    public Obra $obraRecord;

    public string $fecha;

    public function mount(int $obra, string $fecha): void
    {
        abort_if(auth()->user()?->hasRole('operario'), 403);

        $this->obraRecord = Obra::query()->with('cliente')->findOrFail($obra);
        $this->fecha = $fecha;
    }

    public function getTitle(): string
    {
        return $this->obraRecord->nombre;
    }

    public function getRegistros(): Collection
    {
        return Programacion::query()
            ->where('obra_id', $this->obraRecord->id)
            ->whereDate('fecha', $this->fecha)
            ->with(['empleado.user.roles', 'vehiculo'])
            ->orderBy('orden')
            ->orderBy('hora')
            ->get();
    }

    public function getVehiculo(): ?Vehiculo
    {
        return $this->getRegistros()->first(fn (Programacion $r) => $r->vehiculo_id)?->vehiculo;
    }

    public function getHoraMin(): ?Carbon
    {
        return $this->getRegistros()->pluck('hora')->filter()->sortBy(fn ($h) => $h->format('H:i:s'))->first();
    }
}
