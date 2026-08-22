<?php

namespace Tests\Feature;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use App\Filament\Resources\RequerimientoResource\Pages\ListRequerimientos;
use App\Filament\Resources\RequerimientoResource\Pages\ViewRequerimiento;
use App\Filament\Resources\RequerimientoResource\RelationManagers\RequerimientoItemsRelationManager;
use App\Models\CategoriaMaterial;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\Obra;
use App\Models\Requerimiento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class AcabadosDespachoFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function userConRol(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole($rol);

        return $user;
    }

    protected function obra(string $nombre): Obra
    {
        return Obra::create(['nombre' => $nombre, 'cliente_id' => Cliente::query()->value('id')]);
    }

    protected function requerimientoSenaleticaAprobado(Obra $obra, User $solicitante, int $cantidadItems = 1): Requerimiento
    {
        $requerimiento = Requerimiento::create([
            'obra_id' => $obra->id,
            'solicitado_por' => $solicitante->id,
            'aprobado_por' => $solicitante->id,
            'fecha_aprobacion' => now(),
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => EstadoRequerimiento::Aprobado->value,
            'fecha_solicitud' => now(),
        ]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);

        for ($i = 0; $i < $cantidadItems; $i++) {
            $material = Material::create([
                'categoria_id' => $letreros->id,
                'nombre' => "Test Letrero Acabados {$i}",
                'unidad_medida' => 'und',
                'activo' => true,
            ]);

            $requerimiento->items()->create([
                'material_id' => $material->id,
                'cantidad' => 1,
            ]);
        }

        return $requerimiento;
    }

    public function test_el_rol_acabados_ve_solo_senaletica_aprobado_en_su_bandeja(): void
    {
        $acabados = $this->userConRol('acabados');
        $solicitante = $this->userConRol('administrador');

        $obraAprobado = $this->obra('Obra señalética aprobado');
        $pendiente = $this->requerimientoSenaleticaAprobado($obraAprobado, $solicitante);

        // No debe verse: otro tipo, u otro estado.
        $obraMaterial = $this->obra('Obra material');
        Requerimiento::create([
            'obra_id' => $obraMaterial->id,
            'solicitado_por' => $solicitante->id,
            'tipo' => TipoRequerimiento::Material->value,
            'estado' => EstadoRequerimiento::Aprobado->value,
            'fecha_solicitud' => now(),
        ]);

        $obraEnAlistamiento = $this->obra('Obra señalética en alistamiento');
        Requerimiento::create([
            'obra_id' => $obraEnAlistamiento->id,
            'solicitado_por' => $solicitante->id,
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => EstadoRequerimiento::EnAlistamiento->value,
            'fecha_solicitud' => now(),
        ]);

        auth()->login($acabados);
        $visibles = \App\Filament\Resources\RequerimientoResource::getEloquentQuery()->get();

        $this->assertTrue($visibles->contains('id', $pendiente->id));
        // Todo lo que ve acabados debe ser señalética + aprobado, sin importar
        // qué otros requerimientos reales existan ya en la base.
        $this->assertTrue($visibles->every(fn (Requerimiento $r) => $r->tipo === TipoRequerimiento::Señaletica && $r->estado === EstadoRequerimiento::Aprobado));
    }

    public function test_acabados_no_puede_acceder_sin_el_rol(): void
    {
        $sinRol = User::factory()->create();
        $sinRol->assignRole('operario');

        auth()->login($sinRol);

        $this->assertFalse(\App\Filament\Resources\RequerimientoResource::canViewAny());
    }

    public function test_marcar_todos_los_items_preparados_pasa_a_en_alistamiento_y_desaparece_de_acabados(): void
    {
        $acabados = $this->userConRol('acabados');
        $solicitante = $this->userConRol('administrador');
        $obra = $this->obra('Obra transicion en_alistamiento');

        $requerimiento = $this->requerimientoSenaleticaAprobado($obra, $solicitante, 2);
        $items = $requerimiento->items;

        auth()->login($acabados);

        Livewire::test(RequerimientoItemsRelationManager::class, [
            'ownerRecord' => $requerimiento,
            'pageClass' => ViewRequerimiento::class,
        ])
            ->callTableAction('marcarPreparado', $items[0])
            ->callTableAction('marcarPreparado', $items[1]);

        $requerimiento->refresh();
        $this->assertSame(EstadoRequerimiento::EnAlistamiento, $requerimiento->estado);

        // Desaparece de la bandeja de acabados (ya no es 'aprobado').
        $visibles = \App\Filament\Resources\RequerimientoResource::getEloquentQuery()->get();
        $this->assertFalse($visibles->contains('id', $requerimiento->id));
    }

    public function test_pedido_en_alistamiento_aparece_en_bandeja_de_despacho(): void
    {
        $acabados = $this->userConRol('acabados');
        $despacho = $this->userConRol('despacho');
        $solicitante = $this->userConRol('administrador');
        $obra = $this->obra('Obra visible en despacho');

        $requerimiento = $this->requerimientoSenaleticaAprobado($obra, $solicitante, 1);
        $item = $requerimiento->items->first();

        auth()->login($acabados);
        Livewire::test(RequerimientoItemsRelationManager::class, [
            'ownerRecord' => $requerimiento,
            'pageClass' => ViewRequerimiento::class,
        ])->callTableAction('marcarPreparado', $item);

        $requerimiento->refresh();
        $this->assertSame(EstadoRequerimiento::EnAlistamiento, $requerimiento->estado);

        auth()->login($despacho);
        $visiblesDespacho = \App\Filament\Resources\RequerimientoResource::getEloquentQuery()->get();
        $this->assertTrue($visiblesDespacho->contains('id', $requerimiento->id));
    }

    public function test_acabados_no_ve_pedidos_de_despacho_y_viceversa(): void
    {
        $acabados = $this->userConRol('acabados');
        $despacho = $this->userConRol('despacho');
        $solicitante = $this->userConRol('administrador');

        $obraAprobado = $this->obra('Obra cruce aprobado');
        $enAprobado = $this->requerimientoSenaleticaAprobado($obraAprobado, $solicitante, 1);

        $obraAlistamiento = $this->obra('Obra cruce en_alistamiento');
        $enAlistamiento = Requerimiento::create([
            'obra_id' => $obraAlistamiento->id,
            'solicitado_por' => $solicitante->id,
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => EstadoRequerimiento::EnAlistamiento->value,
            'fecha_solicitud' => now(),
        ]);

        auth()->login($acabados);
        $visiblesAcabados = \App\Filament\Resources\RequerimientoResource::getEloquentQuery()->get();
        $this->assertTrue($visiblesAcabados->contains('id', $enAprobado->id));
        $this->assertFalse($visiblesAcabados->contains('id', $enAlistamiento->id));

        auth()->login($despacho);
        $visiblesDespacho = \App\Filament\Resources\RequerimientoResource::getEloquentQuery()->get();
        $this->assertTrue($visiblesDespacho->contains('id', $enAlistamiento->id));
        $this->assertFalse($visiblesDespacho->contains('id', $enAprobado->id));

        // Cruce de permiso sobre el propio item: despacho no puede marcar
        // preparado, acabados no puede verificar despacho.
        $this->assertFalse($enAprobado->puedeGestionarDespacho($acabados));
        $this->assertFalse($enAlistamiento->puedeGestionarAcabados($despacho));
    }
}
