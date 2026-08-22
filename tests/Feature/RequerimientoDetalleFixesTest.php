<?php

namespace Tests\Feature;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
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

class RequerimientoDetalleFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        return $user;
    }

    protected function obra(string $nombre): Obra
    {
        return Obra::create(['nombre' => $nombre, 'cliente_id' => Cliente::query()->value('id')]);
    }

    public function test_adicional_de_no_aparece_en_requerimiento_normal(): void
    {
        $admin = $this->admin();
        $requerimiento = Requerimiento::create([
            'obra_id' => $this->obra('Obra requerimiento normal')->id,
            'solicitado_por' => $admin->id,
            'tipo' => TipoRequerimiento::Material->value,
            'estado' => EstadoRequerimiento::Pendiente->value,
            'fecha_solicitud' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ViewRequerimiento::class, ['record' => $requerimiento->getRouteKey()])
            ->assertDontSee('Adicional de');
    }

    public function test_adicional_de_aparece_con_la_referencia_correcta(): void
    {
        $admin = $this->admin();
        $obra = $this->obra('Obra requerimiento adicional');

        $original = Requerimiento::create([
            'obra_id' => $obra->id,
            'solicitado_por' => $admin->id,
            'tipo' => TipoRequerimiento::Material->value,
            'estado' => EstadoRequerimiento::Entregado->value,
            'fecha_solicitud' => now()->subDay(),
        ]);

        $adicional = Requerimiento::create([
            'obra_id' => $obra->id,
            'requerimiento_original_id' => $original->id,
            'solicitado_por' => $admin->id,
            'tipo' => TipoRequerimiento::Material->value,
            'estado' => EstadoRequerimiento::Pendiente->value,
            'fecha_solicitud' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ViewRequerimiento::class, ['record' => $adicional->getRouteKey()])
            ->assertSee('Adicional de')
            ->assertSee("#{$original->id}");
    }

    public function test_badge_auto_aprobado_aparece_cuando_el_solicitante_se_autoaprueba(): void
    {
        $admin = $this->admin();
        $requerimiento = Requerimiento::create([
            'obra_id' => $this->obra('Obra auto-aprobado')->id,
            'solicitado_por' => $admin->id,
            'aprobado_por' => $admin->id,
            'fecha_aprobacion' => now(),
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => EstadoRequerimiento::Aprobado->value,
            'fecha_solicitud' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ViewRequerimiento::class, ['record' => $requerimiento->getRouteKey()])
            ->assertSee('Auto-aprobado');
    }

    public function test_badge_auto_aprobado_no_aparece_cuando_aprueba_otra_persona(): void
    {
        $admin = $this->admin();
        $otro = User::factory()->create();
        $otro->assignRole('administrador');

        $requerimiento = Requerimiento::create([
            'obra_id' => $this->obra('Obra aprobado por otro')->id,
            'solicitado_por' => $admin->id,
            'aprobado_por' => $otro->id,
            'fecha_aprobacion' => now(),
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => EstadoRequerimiento::Aprobado->value,
            'fecha_solicitud' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ViewRequerimiento::class, ['record' => $requerimiento->getRouteKey()])
            ->assertDontSee('Auto-aprobado');
    }

    public function test_verificar_despacho_guarda_verificado_por_y_fecha_y_se_ve_en_el_detalle(): void
    {
        $admin = $this->admin();
        $despachador = User::factory()->create();
        $despachador->assignRole('despacho');

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $material = Material::create([
            'categoria_id' => $letreros->id,
            'nombre' => 'Test Letrero Verificacion',
            'unidad_medida' => 'und',
            'activo' => true,
        ]);

        $requerimiento = Requerimiento::create([
            'obra_id' => $this->obra('Obra verificacion despacho')->id,
            'solicitado_por' => $admin->id,
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => EstadoRequerimiento::EnAlistamiento->value,
            'fecha_solicitud' => now(),
        ]);

        $item = $requerimiento->items()->create([
            'material_id' => $material->id,
            'cantidad' => 2,
            'preparado' => true,
        ]);

        $item->verificarDespacho($despachador);
        $item->refresh();

        $this->assertTrue($item->verificado_despacho);
        $this->assertSame($despachador->id, $item->verificado_por);
        $this->assertNotNull($item->fecha_verificacion);

        Livewire::actingAs($admin)
            ->test(RequerimientoItemsRelationManager::class, [
                'ownerRecord' => $requerimiento,
                'pageClass' => ViewRequerimiento::class,
            ])
            ->assertSee('Verificado por');

        // Desmarcar (rechazo puntual) limpia verificado_por y fecha_verificacion.
        $item->rechazarDespacho('No coincide con lo pedido');
        $item->refresh();

        $this->assertFalse($item->verificado_despacho);
        $this->assertNull($item->verificado_por);
        $this->assertNull($item->fecha_verificacion);
    }
}
