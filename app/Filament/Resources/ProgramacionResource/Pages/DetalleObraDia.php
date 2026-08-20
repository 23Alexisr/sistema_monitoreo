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

    /**
     * ProgramacionResource::canViewAny() bloquea operario por completo (lo
     * usan ListProgramacions/CreateProgramacion/EditarGrupoDia, que deben
     * seguir bloqueados). Esta página sí debe ser alcanzable por un
     * operario/encargado asignado a la obra, así que se anula el gate
     * automático de recurso (CanAuthorizeResourceAccess) y se reemplaza por
     * canAccess() propio, mismo patrón que EjecutarChecklist.
     */
    public function mountCanAuthorizeResourceAccess(): void
    {
        //
    }

    public function hydrateCanAuthorizeResourceAccess(): void
    {
        //
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['administrador', 'jefe_planta'])) {
            return true;
        }

        if (! $user->hasRole('operario')) {
            return false;
        }

        $obraId = $parameters['obra'] ?? null;
        $obra = $obraId instanceof Obra ? $obraId : ($obraId ? Obra::find($obraId) : null);

        return $obra instanceof Obra && $obra->asignadaAOperario($user);
    }

    public function mount(int $obra, string $fecha): void
    {
        abort_unless(static::canAccess(['obra' => $obra]), 403);

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
