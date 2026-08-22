<?php

namespace Tests\Feature;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use App\Filament\Resources\RequerimientoResource\Pages\ViewRequerimiento;
use App\Filament\Resources\RequerimientoResource\RelationManagers\RequerimientoItemsRelationManager;
use App\Filament\Resources\RequerimientoResource\Pages\CrearRequerimiento;
use App\Models\CategoriaMaterial;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\Obra;
use App\Models\Requerimiento;
use App\Models\TrabajoMaestro;
use App\Models\TrabajoMaterialSugerido;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class RequerimientoItemsSinColumnaSugeridoTest extends TestCase
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

    public function test_tabla_de_items_no_muestra_columna_sugerido(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba tabla items', 'cliente_id' => Cliente::query()->value('id')]);
        $admin = $this->admin();

        $electrico = CategoriaMaterial::firstOrCreate(['nombre' => 'Eléctrico']);
        $material = Material::create([
            'categoria_id' => $electrico->id,
            'nombre' => 'Test Material Tabla Items',
            'unidad_medida' => 'und',
            'activo' => true,
        ]);

        $requerimiento = Requerimiento::create([
            'obra_id' => $obra->id,
            'solicitado_por' => $admin->id,
            'tipo' => TipoRequerimiento::Material->value,
            'estado' => EstadoRequerimiento::Pendiente->value,
            'fecha_solicitud' => now(),
        ]);

        $requerimiento->items()->create([
            'material_id' => $material->id,
            'cantidad' => 3,
            'es_sugerido' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(RequerimientoItemsRelationManager::class, [
                'ownerRecord' => $requerimiento,
                'pageClass' => ViewRequerimiento::class,
            ])
            ->assertSuccessful()
            ->assertDontSee('Sugerido')
            ->assertSee('Test Material Tabla Items')
            ->assertSee('Observaciones');
    }

    public function test_autocompletado_de_sugerencias_sigue_funcionando(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba sugerencias', 'cliente_id' => Cliente::query()->value('id')]);

        $electrico = CategoriaMaterial::firstOrCreate(['nombre' => 'Eléctrico']);
        $material = Material::create([
            'categoria_id' => $electrico->id,
            'nombre' => 'Test Material Sugerido',
            'unidad_medida' => 'und',
            'activo' => true,
        ]);

        $trabajo = TrabajoMaestro::create([
            'categoria_id' => $electrico->id,
            'descripcion' => 'Test Trabajo Con Sugerencia',
            'activo' => true,
        ]);

        TrabajoMaterialSugerido::create([
            'trabajo_maestro_id' => $trabajo->id,
            'material_id' => $material->id,
            'cantidad_sugerida' => 5,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'material')
            ->call('seleccionarTrabajoMaestro', $trabajo->id);

        $sugerencias = $component->instance()->sugerenciasPendientes();

        $this->assertCount(1, $sugerencias);
        $this->assertSame($material->id, $sugerencias->first()->material_id);

        $component->call('agregarSugerido', $material->id);

        $carrito = $component->get('carrito');
        $this->assertTrue($carrito[$material->id]['es_sugerido']);
        $this->assertSame(5.0, (float) $carrito[$material->id]['cantidad']);
    }
}
