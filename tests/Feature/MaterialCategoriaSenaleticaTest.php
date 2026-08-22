<?php

namespace Tests\Feature;

use App\Filament\Resources\MaterialResource\Pages\CreateMaterial;
use App\Models\CategoriaMaterial;
use App\Models\Material;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialCategoriaSenaleticaTest extends TestCase
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

    public function test_categoria_letreros_muestra_ancho_largo_y_oculta_unidad(): void
    {
        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);

        Livewire::actingAs($this->admin())
            ->test(CreateMaterial::class)
            ->fillForm(['categoria_id' => $letreros->id])
            ->assertFormFieldIsVisible('ancho')
            ->assertFormFieldIsVisible('largo')
            ->assertFormFieldIsHidden('unidad_medida')
            ->fillForm([
                'categoria_id' => $letreros->id,
                'nombre' => 'Letrero prohibido fumar',
                'ancho' => '0.40',
                'largo' => '0.30',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $material = Material::where('nombre', 'Letrero prohibido fumar')->firstOrFail();

        $this->assertSame('und', $material->unidad_medida);
        $this->assertSame('0.40', $material->ancho);
        $this->assertSame('0.30', $material->largo);
    }

    public function test_categoria_general_muestra_unidad_y_oculta_ancho_largo(): void
    {
        $electrico = CategoriaMaterial::firstOrCreate(['nombre' => 'Eléctrico']);

        Livewire::actingAs($this->admin())
            ->test(CreateMaterial::class)
            ->fillForm(['categoria_id' => $electrico->id])
            ->assertFormFieldIsHidden('ancho')
            ->assertFormFieldIsHidden('largo')
            ->assertFormFieldIsVisible('unidad_medida')
            ->fillForm([
                'categoria_id' => $electrico->id,
                'nombre' => 'Cable THW 2.5mm',
                'unidad_medida' => 'm',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $material = Material::where('nombre', 'Cable THW 2.5mm')->firstOrFail();

        $this->assertSame('m', $material->unidad_medida);
        $this->assertNull($material->ancho);
        $this->assertNull($material->largo);
    }
}
