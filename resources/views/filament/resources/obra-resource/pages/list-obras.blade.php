<x-filament-panels::page>
    <div style="margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
        <button
            type="button"
            wire:click="setFiltro('activas')"
            style="cursor: pointer; border: none; border-radius: 999px; padding: 6px 16px; font-size: 13px; font-weight: 500; {{ $filtro === 'activas' ? 'background:#111827;color:#fff;' : 'background:#f3f4f6;color:#4b5563;' }}"
        >
            Activas
        </button>
        <button
            type="button"
            wire:click="setFiltro('todas')"
            style="cursor: pointer; border: none; border-radius: 999px; padding: 6px 16px; font-size: 13px; font-weight: 500; {{ $filtro === 'todas' ? 'background:#111827;color:#fff;' : 'background:#f3f4f6;color:#4b5563;' }}"
        >
            Todas
        </button>
    </div>

    @php $obras = $this->getObras(); @endphp

    @if ($obras->isEmpty())
        <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
            <p style="margin: 0 0 14px;">No hay obras {{ $filtro === 'activas' ? 'activas' : '' }} para mostrar.</p>

            @if (\App\Filament\Resources\ObraResource::canCreate())
                <a
                    href="{{ \App\Filament\Resources\ObraResource::getUrl('create') }}"
                    style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; border-radius: 8px; background: #111827; color: #ffffff; padding: 8px 16px; font-size: 13px; font-weight: 600;"
                >
                    <x-heroicon-o-plus style="width: 16px; height: 16px;" />
                    Agregar obra
                </a>
            @endif
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px;">
            @foreach ($obras as $obra)
                @include('filament.resources.obra-resource.pages.partials.obra-card', ['obra' => $obra])
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
