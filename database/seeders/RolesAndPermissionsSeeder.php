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
            'aprobar-checklist',
            'mover-personal',
            'eliminar-items',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $administrador = Role::findOrCreate('administrador');
        $administrador->syncPermissions(Permission::all());

        $jefePlanta = Role::findOrCreate('jefe_planta');
        $jefePlanta->syncPermissions([
            'ver-documentos',
            'ver-ot',
            'ver-fotos',
            'gestionar-checklist',
            'aprobar-checklist',
        ]);

        Role::findOrCreate('operario');
        Role::findOrCreate('almacen');
        Role::findOrCreate('despacho');

        // jefe_proyectos y jefe_ssoma ya tienen lógica activa (aprobar
        // requerimientos de señalética/seguridad respectivamente) pero sin
        // permisos symlink propios todavía, ya que el patrón del repo gatea
        // por rol (hasRole), no por permission.
        Role::findOrCreate('jefe_proyectos');
        Role::findOrCreate('jefe_ssoma');

        // Roles futuros: creados sin permisos activos, listos para activarse
        // en una fase posterior sin necesidad de rediseño.
        Role::findOrCreate('asistente');
        Role::findOrCreate('supervisor');
    }
}
