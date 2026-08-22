<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Usuarios creados desde "Crear Empleado" (formulario interno, sin
     * flujo de registro público) quedaban con email_verified_at null. No
     * era la causa de que no pudieran loguearse (ver
     * User::canAccessPanel(), corregido aparte), pero de todas formas no
     * tiene sentido pedirle verificación de correo a un empleado interno.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intencionalmente sin revertir: no hay forma de distinguir cuáles
        // de estos usuarios ya venían con verified_at null antes de la
        // migración vs. cuáles se verificaron de verdad después.
    }
};
