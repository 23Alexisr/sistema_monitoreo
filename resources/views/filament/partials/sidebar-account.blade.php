@php
    $user = filament()->auth()->user();
    $empleado = $user->empleado;
    $nombre = $empleado?->nombre_completo ?? $user->email;
    $avatarUrl = $user->getFilamentAvatarUrl();
    $rol = $user->getRoleNames()->first();

    $rolLabels = [
        'administrador' => 'Administrador',
        'jefe_planta' => 'Jefe de planta',
        'operario' => 'Operario',
    ];
    $rolColores = [
        'administrador' => '#DC2626',
        'jefe_planta' => '#0EA5E9',
        'operario' => '#10B981',
    ];
@endphp

<div style="border-top: 1px solid rgba(0,0,0,0.08); padding: 12px 14px; display: flex; align-items: center; gap: 10px;">
    @if ($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $nombre }}" style="width: 36px; height: 36px; border-radius: 999px; object-fit: cover; flex-shrink: 0;" />
    @else
        <div style="width: 36px; height: 36px; border-radius: 999px; flex-shrink: 0; background: #6B7280; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">
            {{ mb_strtoupper(mb_substr($nombre, 0, 1)) }}
        </div>
    @endif

    <div style="min-width: 0; flex: 1;">
        <p style="margin: 0; font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $nombre }}</p>
        @if ($rol)
            <span style="display: inline-block; margin-top: 3px; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: {{ $rolColores[$rol] ?? '#6B7280' }}; color: #ffffff;">
                {{ $rolLabels[$rol] ?? ucfirst($rol) }}
            </span>
        @endif
    </div>

    <form method="POST" action="{{ filament()->getLogoutUrl() }}">
        @csrf
        <button
            type="submit"
            title="Cerrar sesión"
            style="cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: none; background: rgba(0,0,0,0.05); color: inherit; flex-shrink: 0;"
        >
            <x-heroicon-o-arrow-left-on-rectangle style="width: 18px; height: 18px;" />
        </button>
    </form>
</div>
