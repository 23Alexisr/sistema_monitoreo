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

    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full justify-center">
            Asignar personal
        </x-filament::button>
    </x-filament-panels::form>
</x-filament-panels::page>
