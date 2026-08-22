<?php

namespace Tests\Feature;

use App\Filament\Resources\RequerimientoResource\Pages\CrearRequerimiento;
use App\Models\CategoriaMaterial;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\Obra;
use App\Models\SubcategoriaMaterial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class CrearRequerimientoSenaleticaFlowTest extends TestCase
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

    public function test_agregar_grupo_completo_agrega_los_que_tienen_medida_fija_y_omite_los_que_no(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba señalética', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $conMedida = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Con Medida',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $sinMedida = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Sin Medida',
            'unidad_medida' => 'und',
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('agregarGrupoCompleto', 'Letreros Informativos');

        $carrito = $component->get('carrito');

        $this->assertArrayHasKey($conMedida->id, $carrito);
        $this->assertSame(1.0, (float) $carrito[$conMedida->id]['cantidad']);
        $this->assertArrayNotHasKey($sinMedida->id, $carrito);
    }

    public function test_agregar_grupo_completo_no_pisa_cantidad_ya_editada(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba señalética 2', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Ya En Carrito',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('agregarGrupoCompleto', 'Letreros Informativos')
            ->call('actualizarCantidadCarrito', $material->id, '5')
            ->call('agregarGrupoCompleto', 'Letreros Informativos');

        $carrito = $component->get('carrito');

        $this->assertSame(5.0, (float) $carrito[$material->id]['cantidad']);
    }

    public function test_actualizar_cantidad_carrito_rechaza_valores_invalidos(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba señalética 3', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Cantidad',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('agregarGrupoCompleto', 'Letreros Informativos')
            ->call('actualizarCantidadCarrito', $material->id, '0');

        $carrito = $component->get('carrito');

        $this->assertSame(1.0, (float) $carrito[$material->id]['cantidad']);
    }

    public function test_cancelar_cierra_el_modal_de_cantidad(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba cancelar modal', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Cancelar Modal',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('elegirMaterial', $material->id)
            ->assertSee('¿Cuánto necesitas?')
            ->assertSet('materialParaCantidadId', $material->id);

        $component
            ->call('cancelarCantidad')
            ->assertDontSee('¿Cuánto necesitas?')
            ->assertSet('materialParaCantidadId', null);
    }

    public function test_revisar_pedido_no_crea_nada_hasta_confirmar(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba revisar pedido', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Revisar Pedido',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('agregarGrupoCompleto', 'Letreros Informativos')
            ->call('revisarPedido')
            ->assertSet('revisandoPedido', true)
            ->assertSee('Revisar pedido')
            ->assertSee('Confirmar pedido')
            ->assertSee($obra->nombre);

        $this->assertDatabaseCount('requerimientos', 0);
        $this->assertDatabaseCount('requerimiento_items', 0);

        // Volver a editar no debe perder lo agregado.
        $component->call('volverAEditarPedido')
            ->assertSet('revisandoPedido', false);

        $carrito = $component->get('carrito');
        $this->assertArrayHasKey($material->id, $carrito);

        // Recién al confirmar se crea el registro.
        $component->call('revisarPedido')->call('enviar');

        $this->assertDatabaseCount('requerimientos', 1);
        $this->assertDatabaseHas('requerimiento_items', [
            'material_id' => $material->id,
            'cantidad' => 1,
        ]);
    }

    public function test_revisar_pedido_rechaza_carrito_vacio(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba carrito vacio', 'cliente_id' => Cliente::query()->value('id')]);

        Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('revisarPedido')
            ->assertSet('revisandoPedido', false);
    }

    public function test_carrito_agrupado_usa_el_mismo_criterio_que_el_catalogo(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba carrito agrupado', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subInformativos = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );
        $subAdicionales = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Adicionales', 'categoria_id' => $letreros->id],
            ['orden' => 2]
        );

        $materialA = Material::create([
            'subcategoria_id' => $subInformativos->id,
            'nombre' => 'Test Letrero Grupo A',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $materialB = Material::create([
            'subcategoria_id' => $subAdicionales->id,
            'nombre' => 'Test Letrero Grupo B',
            'unidad_medida' => 'und',
            'ancho' => 0.20,
            'largo' => 0.20,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('agregarGrupoCompleto', 'Letreros Informativos')
            ->call('agregarGrupoCompleto', 'Letreros Adicionales');

        $agrupado = $component->instance()->carritoAgrupado();

        $this->assertTrue($agrupado->has('Letreros Informativos'));
        $this->assertTrue($agrupado->has('Letreros Adicionales'));
        $this->assertSame('Test Letrero Grupo A', $agrupado->get('Letreros Informativos')->first()->nombre);
        $this->assertSame('Test Letrero Grupo B', $agrupado->get('Letreros Adicionales')->first()->nombre);

        $component->assertSee('Letreros Informativos')
            ->assertSee('Letreros Adicionales')
            ->assertSee('Test Letrero Grupo A')
            ->assertSee('Test Letrero Grupo B');
    }

    public function test_stepper_incrementa_y_decrementa_de_a_uno_sin_bajar_de_uno(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba stepper', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Stepper',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('agregarGrupoCompleto', 'Letreros Informativos');

        $this->assertSame(1.0, (float) $component->get('carrito')[$material->id]['cantidad']);

        $component->call('incrementarCantidadCarrito', $material->id)
            ->call('incrementarCantidadCarrito', $material->id);
        $this->assertSame(3.0, (float) $component->get('carrito')[$material->id]['cantidad']);

        $component->call('decrementarCantidadCarrito', $material->id);
        $this->assertSame(2.0, (float) $component->get('carrito')[$material->id]['cantidad']);

        // No baja de 1.
        $component->call('decrementarCantidadCarrito', $material->id)
            ->call('decrementarCantidadCarrito', $material->id)
            ->call('decrementarCantidadCarrito', $material->id);
        $this->assertSame(1.0, (float) $component->get('carrito')[$material->id]['cantidad']);
    }

    public function test_modo_senaletica_rechaza_cantidad_decimal_en_el_modal(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba decimal senaletica', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Letreros Informativos', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Test Letrero Decimal',
            'unidad_medida' => 'und',
            'ancho' => 0.40,
            'largo' => 0.30,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('elegirMaterial', $material->id)
            ->set('cantidadTexto', '1.85')
            ->call('confirmarCantidad');

        $this->assertArrayNotHasKey($material->id, $component->get('carrito'));

        $component->set('cantidadTexto', '3')->call('confirmarCantidad');

        $this->assertSame(3.0, (float) $component->get('carrito')[$material->id]['cantidad']);
    }

    public function test_modo_material_sigue_permitiendo_cantidad_decimal(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba decimal material', 'cliente_id' => Cliente::query()->value('id')]);

        $electrico = CategoriaMaterial::firstOrCreate(['nombre' => 'Eléctrico']);

        $material = Material::create([
            'categoria_id' => $electrico->id,
            'nombre' => 'Test Cable Decimal',
            'unidad_medida' => 'm',
            'ancho' => 0.01,
            'largo' => 0.01,
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'material')
            ->call('elegirMaterial', $material->id)
            ->set('cantidadTexto', '2.5')
            ->call('confirmarCantidad');

        $this->assertSame(2.5, (float) $component->get('carrito')[$material->id]['cantidad']);
    }

    public function test_item_no_catalogado_exige_ancho_y_largo(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba item no catalogado', 'cliente_id' => Cliente::query()->value('id')]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('abrirModalManual')
            ->set('descripcionManual', 'Letrero especial de prueba')
            ->set('cantidadManualTexto', '2')
            ->call('confirmarManual');

        // Sin ancho ni largo: no se agrega nada, quedan los dos errores inline.
        $this->assertEmpty($component->get('carrito'));
        $this->assertSame('Ingresa el ancho del letrero', $component->get('errorAnchoManual'));
        $this->assertSame('Ingresa el largo del letrero', $component->get('errorLargoManual'));
        $component->assertSee('Ingresa el ancho del letrero')
            ->assertSee('Ingresa el largo del letrero')
            ->assertDontSee('Medidas (opcional)');

        // Con ancho y largo: se agrega normalmente, mapeado a ancho_pedido/largo_pedido, y los errores se limpian.
        $component->set('anchoManualTexto', '1.44')
            ->set('largoManualTexto', '0.58')
            ->call('confirmarManual');

        $this->assertNull($component->get('errorAnchoManual'));
        $this->assertNull($component->get('errorLargoManual'));

        $carrito = $component->get('carrito');
        $this->assertCount(1, $carrito);
        $item = collect($carrito)->first();
        $this->assertSame(1.44, (float) $item['ancho_pedido']);
        $this->assertSame(0.58, (float) $item['largo_pedido']);

        $component->call('revisarPedido')->call('enviar');

        $this->assertDatabaseHas('requerimiento_items', [
            'descripcion_manual' => 'Letrero especial de prueba',
            'ancho_pedido' => 1.44,
            'largo_pedido' => 0.58,
        ]);
    }

    public function test_medidas_manuales_admiten_decimales_incluso_en_senaletica(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba medida decimal manual', 'cliente_id' => Cliente::query()->value('id')]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->call('abrirModalManual')
            ->set('descripcionManual', 'Letrero decimal')
            ->set('cantidadManualTexto', '1')
            ->set('anchoManualTexto', '0.35')
            ->set('largoManualTexto', '0.20')
            ->call('confirmarManual');

        $carrito = $component->get('carrito');
        $this->assertCount(1, $carrito);
        $item = collect($carrito)->first();
        $this->assertSame(0.35, (float) $item['ancho_pedido']);
        $this->assertSame(0.20, (float) $item['largo_pedido']);
    }

    public function test_agregar_dos_veces_un_material_sin_medida_fija_crea_dos_lineas_independientes(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba tapa surtidor', 'cliente_id' => Cliente::query()->value('id')]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);
        $subcategoria = SubcategoriaMaterial::firstOrCreate(
            ['nombre' => 'Vinil para Surtidor', 'categoria_id' => $letreros->id],
            ['orden' => 1]
        );

        // Sin ancho/largo en catálogo, igual que "Tapa surtidor combustible líquido" real.
        $material = Material::create([
            'subcategoria_id' => $subcategoria->id,
            'nombre' => 'Tapa surtidor combustible líquido',
            'unidad_medida' => 'und',
            'activo' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica');

        // Primera medida.
        $component->call('elegirMaterial', $material->id)
            ->set('cantidadTexto', '2')
            ->set('anchoPedidoTexto', '0.40')
            ->set('largoPedidoTexto', '0.30')
            ->call('confirmarCantidad');

        $this->assertCount(1, $component->get('carrito'));

        // Intenta agregar "otro del mismo pero con otra medida": vuelve a
        // elegir el mismo material_id — el modal debe abrir en blanco, no
        // precargado con la primera medida (esa era la causa del bug).
        $component->call('elegirMaterial', $material->id);
        $this->assertNull($component->get('cantidadTexto'));
        $this->assertNull($component->get('anchoPedidoTexto'));
        $this->assertNull($component->get('largoPedidoTexto'));

        $component->set('cantidadTexto', '3')
            ->set('anchoPedidoTexto', '0.55')
            ->set('largoPedidoTexto', '0.45')
            ->call('confirmarCantidad');

        // Debe haber DOS líneas independientes, no una sola pisada.
        $carrito = $component->get('carrito');
        $this->assertCount(2, $carrito);

        $lineasDelMaterial = collect($carrito)->where('material_id', $material->id);
        $this->assertCount(2, $lineasDelMaterial);

        $medidas = $lineasDelMaterial->map(fn ($linea) => [$linea['ancho_pedido'], $linea['largo_pedido']])->values()->all();
        $this->assertContains([0.4, 0.3], $medidas);
        $this->assertContains([0.55, 0.45], $medidas);

        // El indicador del tile del catálogo también debe reflejar ambas.
        $this->assertTrue($component->instance()->carritoTieneMaterial($material->id));
        $this->assertSame(5.0, $component->instance()->carritoCantidadMaterial($material->id));
    }

    public function test_pagina_de_senaletica_renderiza_sin_errores_con_boton_de_grupo(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba señalética 4', 'cliente_id' => Cliente::query()->value('id')]);

        Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->assertSee('Agregar todo el grupo')
            ->assertSuccessful();
    }

    public function test_solo_queda_un_componente_de_carrito_unificado(): void
    {
        $obra = Obra::create(['nombre' => 'Obra de prueba un solo carrito', 'cliente_id' => Cliente::query()->value('id')]);

        Livewire::actingAs($this->admin())
            ->test(CrearRequerimiento::class)
            ->set('obraId', $obra->id)
            ->set('modoFlujo', 'señaletica')
            ->assertDontSee('Ver ▴')
            ->assertDontSee('Ocultar ▾')
            ->assertDontSeeHtml('resumenAbierto')
            ->assertSeeHtml('pedidoAbierto');
    }
}
