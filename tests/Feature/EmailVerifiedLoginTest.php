<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EmailVerifiedLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_funciona_con_email_verified_at_null_si_el_rol_esta_permitido(): void
    {
        $user = User::create([
            'email' => 'test.sin.verificar@example.test',
            'password' => 'password123',
        ]);
        $user->assignRole('despacho');

        $this->assertNull($user->email_verified_at);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'test.sin.verificar@example.test',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_login_falla_para_rol_no_permitido_en_can_access_panel_con_el_mismo_mensaje_generico(): void
    {
        // 'asistente' es un rol futuro sin lógica activa (ver seeder):
        // no está en User::canAccessPanel() a propósito, no es un bug.
        $user = User::create([
            'email' => 'test.rol.no.permitido@example.test',
            'password' => 'password123',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('asistente');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'test.rol.no.permitido@example.test',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }
}
