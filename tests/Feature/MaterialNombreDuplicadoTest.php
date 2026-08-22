<?php

namespace Tests\Feature;

use App\Filament\Resources\MaterialResource\Pages\CreateMaterial;
use App\Models\CategoriaMaterial;
use App\Models\SubcategoriaMaterial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialNombreDuplicadoTest extends TestCase
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

    public function test_se_pueden_crear_dos_materiales_con_el_mismo_nombre_y_medida_distinta(): void
    {
        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $sub = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        Livewire::actingAs($this->admin())
            ->test(CreateMaterial::class)
            ->fillForm([
                'categoria_id' => $sub->categoria_id,
                'subcategoria_id' => $sub->id,
                'nombre' => 'Tapa vinil líquidos',
                'unidad_medida' => 'und',
                'ancho' => '0.30',
                'largo' => '0.20',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::actingAs($this->admin())
            ->test(CreateMaterial::class)
            ->fillForm([
                'categoria_id' => $sub->categoria_id,
                'subcategoria_id' => $sub->id,
                'nombre' => 'Tapa vinil líquidos',
                'unidad_medida' => 'und',
                'ancho' => '0.45',
                'largo' => '0.35',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $materiales = \App\Models\Material::where('nombre', 'Tapa vinil líquidos')->get();

        $this->assertCount(2, $materiales);
        $this->assertNotSame(
            $materiales->first()->codigo,
            $materiales->last()->codigo,
            'Los dos materiales deben tener códigos distintos.'
        );
        $this->assertEqualsWithDelta(0.30, (float) $materiales->first()->ancho, 0.001);
        $this->assertEqualsWithDelta(0.45, (float) $materiales->last()->ancho, 0.001);
    }
}
