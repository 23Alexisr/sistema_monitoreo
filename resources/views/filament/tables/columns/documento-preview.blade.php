@php
    $record = $getRecord();
@endphp

<div style="width: 100%; aspect-ratio: 4 / 3; border-radius: 0.75rem; overflow: hidden; background: rgb(243 244 246);">
    @if ($record->esImagen())
        <img
            src="{{ $record->urlPublica() }}"
            alt="{{ $record->tipoLabel() }}"
            loading="lazy"
            style="width: 100%; height: 100%; object-fit: cover;"
        />
    @else
        <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.375rem; color: rgb(107 114 128);">
            <x-heroicon-o-document-text style="height: 2.5rem; width: 2.5rem;" />
            <span style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em;">{{ $record->extension() }}</span>
        </div>
    @endif
</div>
