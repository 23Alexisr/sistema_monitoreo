@php
    $gridDirection = $getGridDirection() ?? 'column';
    $hasInlineLabel = $hasInlineLabel();
    $id = $getId();
    $isDisabled = $isDisabled();
    $isInline = $isInline();
    $isMultiple = $isMultiple();
    $statePath = $getStatePath();
    $areButtonLabelsHidden = $areButtonLabelsHidden();

    /**
     * Chips con avatar: solo si el campo es un
     * App\Filament\Forms\Components\AvatarToggleButtons (o subclase) con
     * ->avatars() configurado. ToggleButtons normales no se ven afectados.
     */
    $hasAvatars = method_exists($field, 'getAvatarUrl');
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :has-inline-label="$hasInlineLabel"
>
    <x-slot
        name="label"
        @class([
            'sm:pt-1.5' => $hasInlineLabel,
        ])
    >
        {{ $getLabel() }}
    </x-slot>

    @if ($hasAvatars)
        <style>
            .fi-avatar-toggle-chip {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 5px 14px 5px 6px;
                border-radius: 999px;
                border: 1px solid var(--border-strong, #e5e7eb);
                background: var(--surface-1, transparent);
                color: var(--text-secondary, #6b7280);
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: border-color .1s, color .1s, background-color .1s;
            }

            .fi-avatar-toggle-chip img {
                width: 22px;
                height: 22px;
                border-radius: 999px;
                object-fit: cover;
                flex-shrink: 0;
            }

            input:checked + .fi-avatar-toggle-chip {
                border-color: #F59E0B;
                background: rgba(245, 158, 11, 0.1);
                color: #92400E;
            }

            input:disabled + .fi-avatar-toggle-chip {
                opacity: .5;
                cursor: not-allowed;
            }
        </style>
    @endif

    <x-filament::grid
        :default="$getColumns('default')"
        :sm="$getColumns('sm')"
        :md="$getColumns('md')"
        :lg="$getColumns('lg')"
        :xl="$getColumns('xl')"
        :two-xl="$getColumns('2xl')"
        :is-grid="! $isInline"
        :direction="$gridDirection"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($attributes)
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'fi-fo-toggle-buttons gap-3',
                    '-mt-3' => (! $isInline) && ($gridDirection === 'column'),
                    'flex flex-wrap' => $isInline,
                ])
        "
    >
        @foreach ($getOptions() as $value => $label)
            @php
                $inputId = "{$id}-{$value}";
                $shouldOptionBeDisabled = $isDisabled || $isOptionDisabled($value, $label);
            @endphp

            <div
                @class([
                    'break-inside-avoid pt-3' => (! $isInline) && ($gridDirection === 'column'),
                ])
            >
                <input
                    @if ($loop->first && $isAutofocused()) autofocus @endif
                    @disabled($shouldOptionBeDisabled)
                    id="{{ $inputId }}"
                    @if (! $isMultiple)
                        name="{{ $id }}"
                    @endif
                    type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                    value="{{ $value }}"
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
                    {{ $getExtraInputAttributeBag()->class(['peer pointer-events-none absolute opacity-0']) }}
                />

                @if ($hasAvatars)
                    <label for="{{ $inputId }}" class="fi-avatar-toggle-chip">
                        @if ($url = $field->getAvatarUrl($value))
                            <img src="{{ $url }}" alt="" />
                        @endif
                        <span>{{ $label }}</span>
                    </label>
                @else
                    <x-filament::button
                        :color="$getColor($value)"
                        :disabled="$shouldOptionBeDisabled"
                        :for="$inputId"
                        :icon="$getIcon($value)"
                        :label-sr-only="$areButtonLabelsHidden"
                        tag="label"
                    >
                        {{ $label }}
                    </x-filament::button>
                @endif
            </div>
        @endforeach
    </x-filament::grid>
</x-dynamic-component>
