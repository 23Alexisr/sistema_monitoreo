<x-filament-panels::page>
    <style>
        :root {
            --text-secondary: #6b7280;
            --border-strong: #d1d5db;
            --surface-1: #f9fafb;
        }

        .dark {
            --text-secondary: #9ca3af;
            --border-strong: rgba(255, 255, 255, 0.24);
            --surface-1: rgba(255, 255, 255, 0.05);
        }
    </style>

    <a
        href="{{ \App\Filament\Resources\ProgramacionResource::getUrl('detalle', ['obra' => $obraRecord->id, 'fecha' => $fecha]) }}"
        style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 8px 0; margin-bottom: 12px; font-size: 14px; font-weight: 600; color: #6b7280;"
    >
        <x-heroicon-o-arrow-left style="width: 18px; height: 18px;" />
        Volver al detalle
    </a>

    <x-filament-panels::form wire:submit="guardar">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full justify-center">
            Guardar cambios
        </x-filament::button>
    </x-filament-panels::form>
</x-filament-panels::page>
