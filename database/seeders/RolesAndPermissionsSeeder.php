<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'ver-documentos',
            'ver-ot',
            'ver-fotos',
            'gestionar-checklist',
            'mover-personal',
            'eliminar-items',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $jefeCuadrilla = Role::findOrCreate('jefe_cuadrilla');
        $jefeCuadrilla->syncPermissions([
            'ver-documentos',
            'ver-ot',
            'ver-fotos',
        ]);

        $supervisor = Role::findOrCreate('supervisor');
        $supervisor->syncPermissions([
            'ver-documentos',
            'ver-ot',
            'ver-fotos',
            'gestionar-checklist',
        ]);

        $administrador = Role::findOrCreate('administrador');
        $administrador->syncPermissions(Permission::all());
    }
}
