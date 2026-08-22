<?php

namespace Tests\Feature;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use App\Filament\Resources\RequerimientoResource;
use App\Filament\Resources\RequerimientoResource\Pages\PrepararSenaletica;
use App\Filament\Resources\RequerimientoResource\Pages\VerificarDespacho;
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

class ChecklistAcabadosDespachoTest extends TestCase
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

    protected function requerimientoConItems(Obra $obra, User $solicitante, EstadoRequerimiento $estado, int $cantidadItems = 2): Requerimiento
    {
        $requerimiento = Requerimiento::create([
            'obra_id' => $obra->id,
            'solicitado_por' => $solicitante->id,
            'tipo' => TipoRequerimiento::Señaletica->value,
            'estado' => $estado->value,
            'fecha_solicitud' => now(),
        ]);

        $letreros = CategoriaMaterial::firstOrCreate(['nombre' => 'Letreros']);

        for ($i = 0; $i < $cantidadItems; $i++) {
            $material = Material::create([
                'categoria_id' => $letreros->id,
                'nombre' => "Test Letrero Checklist {$i}",
                'unidad_medida' => 'und',
                'ancho' => 0.30,
                'largo' => 0.20,
                'activo' => true,
            ]);

            $requerimiento->items()->create([
                'material_id' => $material->id,
                'cantidad' => 1,
                'preparado' => $estado === EstadoRequerimiento::EnAlistamiento,
            ]);
        }

        return $requerimiento;
    }

    public function test_fila_de_la_bandeja_redirige_a_acabados_a_su_checklist(): void
    {
        $acabados = $this->userConRol('acabados');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist acabados url'), $admin, EstadoRequerimiento::Aprobado);

        auth()->login($acabados);

        $url = RequerimientoResource::getUrlSegunRol($requerimiento);

        $this->assertStringContainsString('/preparar', $url);
    }

    public function test_fila_de_la_bandeja_redirige_a_despacho_a_su_checklist(): void
    {
        $despacho = $this->userConRol('despacho');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist despacho url'), $admin, EstadoRequerimiento::EnAlistamiento);

        auth()->login($despacho);

        $url = RequerimientoResource::getUrlSegunRol($requerimiento);

        $this->assertStringContainsString('/verificar', $url);
    }

    public function test_admin_sigue_yendo_a_la_vista_de_detalle_normal(): void
    {
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist admin url'), $admin, EstadoRequerimiento::Aprobado);

        auth()->login($admin);

        $url = RequerimientoResource::getUrlSegunRol($requerimiento);

        $this->assertStringNotContainsString('/preparar', $url);
        $this->assertStringNotContainsString('/verificar', $url);
    }

    public function test_checklist_de_acabados_marca_items_y_transiciona_al_completar(): void
    {
        $acabados = $this->userConRol('acabados');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist marcar'), $admin, EstadoRequerimiento::Aprobado, 2);
        $items = $requerimiento->items;

        $component = Livewire::actingAs($acabados)
            ->test(PrepararSenaletica::class, ['record' => $requerimiento->getRouteKey()])
            ->assertSuccessful();

        $component->call('alternarPreparado', $items[0]->id);
        $requerimiento->refresh();
        $this->assertSame(EstadoRequerimiento::Aprobado, $requerimiento->estado);

        $component->call('alternarPreparado', $items[1]->id);
        $requerimiento->refresh();

        $this->assertSame(EstadoRequerimiento::EnAlistamiento, $requerimiento->estado);
        $component->assertSee('Todo preparado');
    }

    public function test_checklist_de_acabados_permite_desmarcar(): void
    {
        $acabados = $this->userConRol('acabados');
        $admin = $this->userConRol('administrador');
        // 2 items: marcar y desmarcar el primero sin completar el pedido
        // (con 1 solo item, marcarlo ya transiciona el pedido y bloquea
        // el checklist — comportamiento correcto, no aplica a este caso).
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist desmarcar'), $admin, EstadoRequerimiento::Aprobado, 2);
        $item = $requerimiento->items->first();

        $component = Livewire::actingAs($acabados)
            ->test(PrepararSenaletica::class, ['record' => $requerimiento->getRouteKey()])
            ->call('alternarPreparado', $item->id);

        $this->assertTrue($item->fresh()->preparado);

        $component->call('alternarPreparado', $item->id);
        $this->assertFalse($item->fresh()->preparado);
    }

    public function test_acabados_no_puede_acceder_al_checklist_de_un_pedido_ajeno_a_su_estado(): void
    {
        $acabados = $this->userConRol('acabados');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist bloqueado'), $admin, EstadoRequerimiento::EnAlistamiento);

        // Un requerimiento en_alistamiento queda fuera del scope de acabados
        // (getEloquentQuery), así que Filament ni siquiera resuelve el
        // registro para esta ruta — 404, no 403 (mismo comportamiento que
        // el resto de recursos de este panel para registros fuera de scope).
        $this->actingAs($acabados)
            ->get(RequerimientoResource::getUrl('preparar', ['record' => $requerimiento]))
            ->assertNotFound();
    }

    public function test_checklist_de_despacho_verifica_items_y_permite_rechazar(): void
    {
        $despacho = $this->userConRol('despacho');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist despacho verificar'), $admin, EstadoRequerimiento::EnAlistamiento, 2);
        $items = $requerimiento->items;

        $component = Livewire::actingAs($despacho)
            ->test(VerificarDespacho::class, ['record' => $requerimiento->getRouteKey()])
            ->assertSuccessful();

        $component->call('verificarItem', $items[0]->id);
        $item0 = $items[0]->fresh();
        $this->assertTrue($item0->verificado_despacho);
        $this->assertSame($despacho->id, $item0->verificado_por);

        // Rechazar el segundo: vuelve a manos de acabados (preparado=false).
        $component->call('abrirRechazo', $items[1]->id)
            ->set('motivoRechazoTexto', 'No coincide la medida')
            ->call('confirmarRechazo');

        $item1 = $items[1]->fresh();
        $this->assertFalse($item1->preparado);
        $this->assertFalse($item1->verificado_despacho);

        $requerimiento->refresh();
        $this->assertSame(EstadoRequerimiento::Aprobado, $requerimiento->estado);
    }

    public function test_checklist_de_despacho_avisa_cuando_el_pedido_vuelve_a_preparacion(): void
    {
        $despacho = $this->userConRol('despacho');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist aviso rechazo'), $admin, EstadoRequerimiento::EnAlistamiento, 2);
        $items = $requerimiento->items;

        $component = Livewire::actingAs($despacho)
            ->test(VerificarDespacho::class, ['record' => $requerimiento->getRouteKey()]);

        // Rechazar un ítem hace retroceder TODO el pedido a "aprobado".
        $component->call('abrirRechazo', $items[0]->id)
            ->set('motivoRechazoTexto', 'prueba de rechazo')
            ->call('confirmarRechazo');

        $requerimiento->refresh();
        $this->assertSame(EstadoRequerimiento::Aprobado, $requerimiento->estado);

        // La pantalla debe avisar, no seguir mostrando el checklist activo
        // (el título "Verificar despacho" sigue en el header, por eso no se
        // puede chequear la ausencia total de la palabra "Verificar").
        $component->assertSee('volvió a preparación')
            ->assertDontSee('Checklist de verificación');

        // Intentar verificar el OTRO ítem (que seguía preparado) no debe
        // hacer nada — el pedido ya no está en manos de despacho.
        $component->call('verificarItem', $items[1]->id);
        $this->assertFalse($items[1]->fresh()->verificado_despacho);
    }

    public function test_checklist_de_despacho_completo_pasa_a_entregado(): void
    {
        $despacho = $this->userConRol('despacho');
        $admin = $this->userConRol('administrador');
        $requerimiento = $this->requerimientoConItems($this->obra('Obra checklist despacho completo'), $admin, EstadoRequerimiento::EnAlistamiento, 1);
        $item = $requerimiento->items->first();

        $component = Livewire::actingAs($despacho)
            ->test(VerificarDespacho::class, ['record' => $requerimiento->getRouteKey()])
            ->call('verificarItem', $item->id);

        $requerimiento->refresh();
        $this->assertSame(EstadoRequerimiento::Entregado, $requerimiento->estado);
        $component->assertSee('Todo verificado');
    }
}
