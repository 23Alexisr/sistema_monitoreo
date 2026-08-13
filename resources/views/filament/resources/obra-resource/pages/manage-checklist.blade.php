<x-filament-panels::page>
    @if ($this->checklist)
        <div class="mb-6">
            <x-filament::badge :color="$this->record->avance_pct >= 100 ? 'success' : 'warning'">
                Avance de la obra: {{ $this->record->avance_pct }}%
            </x-filament::badge>
        </div>

        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}

            <x-filament::button type="submit">
                Guardar checklist
            </x-filament::button>
        </x-filament-panels::form>
    @else
        <p class="text-sm text-gray-500">
            Esta obra todavía no tiene una Orden de Trabajo registrada. Se debe crear la OT primero desde la pestaña "Editar".
        </p>
    @endif
</x-filament-panels::page>
