@php
    $fechaCarbon = \Illuminate\Support\Carbon::parse($fecha);
    $esHoy = $fechaCarbon->isToday();
    $esManana = $fechaCarbon->isTomorrow();
    $esAyer = $fechaCarbon->isYesterday();

    $subtitulo = match (true) {
        $esHoy => 'Programación de hoy',
        $esManana => 'Programación de mañana',
        $esAyer => 'Programación de ayer',
        default => 'Programación del día',
    };

    $tituloFecha = \Illuminate\Support\Str::ucfirst($fechaCarbon->locale('es')->translatedFormat('l d \d\e F'));

    $resumen = $this->getResumen();
    $obrasDelDia = $this->getObrasDelDia();
@endphp

<x-filament-panels::page>
    <div style="display: flex; align-items: center; justify-content: center; gap: 14px; margin-bottom: 22px;">
        <button
            type="button"
            wire:click="diaAnterior"
            style="cursor: pointer; display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; flex-shrink: 0; border: 1px solid #e5e7eb; border-radius: 999px; background: #ffffff;"
        >
            <x-heroicon-o-chevron-left style="width: 16px; height: 16px; color: #374151;" />
        </button>

        <div style="text-align: center; min-width: 220px;">
            <p style="margin: 0; font-size: 17px; font-weight: 700; color: #111827;">{{ $tituloFecha }}</p>
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 2px;">
                <p style="margin: 0; font-size: 12.5px; color: #6b7280;">{{ $subtitulo }}</p>
                @unless ($esHoy)
                    <button type="button" wire:click="irAHoy" style="cursor: pointer; border: none; background: none; padding: 0; font-size: 12.5px; font-weight: 600; color: #F59E0B; text-decoration: underline;">
                        Ir a hoy
                    </button>
                @endunless
            </div>
        </div>

        <button
            type="button"
            wire:click="diaSiguiente"
            style="cursor: pointer; display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; flex-shrink: 0; border: 1px solid #e5e7eb; border-radius: 999px; background: #ffffff;"
        >
            <x-heroicon-o-chevron-right style="width: 16px; height: 16px; color: #374151;" />
        </button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
        <div style="border-radius: 12px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
            <p style="margin: 0; font-size: 22px; font-weight: 700; color: #111827;">{{ $resumen['totalObras'] }}</p>
            <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">obras con personal hoy</p>
        </div>
        <div style="border-radius: 12px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
            <p style="margin: 0; font-size: 22px; font-weight: 700; color: #111827;">{{ $resumen['totalPersonas'] }}</p>
            <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">personas programadas</p>
        </div>
        <div style="border-radius: 12px; border: 1px solid {{ $resumen['obrasSinEncargado'] > 0 ? '#FDE68A' : '#e5e7eb' }}; background: {{ $resumen['obrasSinEncargado'] > 0 ? '#FFFBEB' : '#ffffff' }}; padding: 14px 16px;">
            <p style="margin: 0; font-size: 22px; font-weight: 700; color: {{ $resumen['obrasSinEncargado'] > 0 ? '#92400E' : '#111827' }};">{{ $resumen['obrasSinEncargado'] }}</p>
            <p style="margin: 2px 0 0; font-size: 12px; color: {{ $resumen['obrasSinEncargado'] > 0 ? '#92400E' : '#6b7280' }};">obras sin encargado</p>
        </div>
    </div>

    @if ($obrasDelDia->isEmpty())
        <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
            No hay personal programado para este día.
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
            @foreach ($obrasDelDia as $item)
                @include('filament.resources.programacion-resource.pages.partials.obra-dia-card', ['item' => $item])
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
