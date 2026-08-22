<?php

namespace Tests\Feature;

use App\Filament\Resources\EmpleadoResource\Pages\CreateEmpleado;
use App\Filament\Resources\EmpleadoResource\Pages\EditEmpleado;
use App\Filament\Resources\RequerimientoResource;
use App\Models\Empleado;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EmpleadoRolAccesoTest extends TestCase
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

    public function test_crear_empleado_con_rol_acabados_le_da_acceso_a_preparacion(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateEmpleado::class)
            ->fillForm([
                'nombre_completo' => 'Test Empleado Acabados',
                'dni' => '10000001',
                'estado' => 'activo',
                'email' => 'test.acabados@example.test',
                'password' => 'password123',
                'rol' => 'acabados',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $empleado = Empleado::where('dni', '10000001')->firstOrFail();

        $this->assertNotNull($empleado->user_id);
        $this->assertTrue($empleado->user->hasRole('acabados'));
        $this->assertNotNull($empleado->user->email_verified_at);

        // Puede acceder a la bandeja/pantalla de preparación (Requerimientos)...
        auth()->login($empleado->user);
        $this->assertTrue(RequerimientoResource::canViewAny());

        // ...y puede efectivamente loguearse (canAccessPanel, gate real de Filament).
        $panel = \Filament\Facades\Filament::getDefaultPanel();
        $this->assertTrue($empleado->user->canAccessPanel($panel));
    }

    public function test_asignar_rol_despacho_a_empleado_sin_rol_previo_le_da_acceso(): void
    {
        $admin = $this->admin();
        $userSinRol = User::create(['email' => 'sin.rol@example.test', 'password' => 'password123']);
        $empleado = Empleado::create([
            'user_id' => $userSinRol->id,
            'nombre_completo' => 'Test Empleado Sin Rol',
            'dni' => '10000002',
            'estado' => 'activo',
        ]);

        // Editar sin rol previo: el campo debe precargar vacío, sin error.
        $component = Livewire::actingAs($admin)->test(EditEmpleado::class, ['record' => $empleado->getRouteKey()]);
        $component->assertFormSet(['rol' => null]);

        $component->fillForm([
            'email' => $userSinRol->email,
            'rol' => 'despacho',
        ])->call('save')->assertHasNoFormErrors();

        $userSinRol->refresh();
        $this->assertTrue($userSinRol->hasRole('despacho'));
        $this->assertNotNull($userSinRol->email_verified_at);

        auth()->login($userSinRol);
        $this->assertTrue(RequerimientoResource::canViewAny());
    }

    public function test_cambiar_rol_de_acabados_a_despacho_reemplaza_no_acumula(): void
    {
        $admin = $this->admin();
        $user = User::create(['email' => 'cambio.rol@example.test', 'password' => 'password123']);
        $user->assignRole('acabados');
        $empleado = Empleado::create([
            'user_id' => $user->id,
            'nombre_completo' => 'Test Empleado Cambio Rol',
            'dni' => '10000003',
            'estado' => 'activo',
        ]);

        // Precarga el rol actual al editar.
        Livewire::actingAs($admin)
            ->test(EditEmpleado::class, ['record' => $empleado->getRouteKey()])
            ->assertFormSet(['rol' => 'acabados'])
            ->fillForm([
                'email' => $user->email,
                'rol' => 'despacho',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertTrue($user->hasRole('despacho'));
        $this->assertFalse($user->hasRole('acabados'));
        $this->assertCount(1, $user->roles);
    }

    public function test_agregar_acceso_por_primera_vez_al_editar_deja_email_verificado(): void
    {
        $admin = $this->admin();
        $empleado = Empleado::create([
            'nombre_completo' => 'Test Empleado Sin Acceso Previo',
            'dni' => '10000005',
            'estado' => 'activo',
        ]);

        $this->assertNull($empleado->user_id);

        Livewire::actingAs($admin)
            ->test(EditEmpleado::class, ['record' => $empleado->getRouteKey()])
            ->fillForm([
                'email' => 'primera.vez@example.test',
                'password' => 'password123',
                'rol' => 'despacho',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $empleado->refresh();

        $this->assertNotNull($empleado->user_id);
        $this->assertNotNull($empleado->user->email_verified_at);
        $this->assertTrue($empleado->user->hasRole('despacho'));
    }

    public function test_crear_empleado_sin_email_ni_password_no_muestra_rol_ni_crea_usuario(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateEmpleado::class)
            ->assertFormFieldIsHidden('rol')
            ->fillForm([
                'nombre_completo' => 'Test Empleado Sin Acceso',
                'dni' => '10000004',
                'estado' => 'activo',
                'especialidad' => 'auxiliar',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $empleado = Empleado::where('dni', '10000004')->firstOrFail();

        $this->assertNull($empleado->user_id);
    }
}
