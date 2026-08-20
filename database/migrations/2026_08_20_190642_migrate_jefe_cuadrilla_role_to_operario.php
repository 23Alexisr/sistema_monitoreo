<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $jefeCuadrilla = Role::findByName('jefe_cuadrilla');

        if (! $jefeCuadrilla) {
            return;
        }

        foreach ($jefeCuadrilla->users as $user) {
            $user->syncRoles(['operario']);
        }

        $jefeCuadrilla->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::findOrCreate('jefe_cuadrilla');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
