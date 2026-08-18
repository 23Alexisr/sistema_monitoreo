<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\ToggleButtons;

/**
 * ToggleButtons con avatar (imagen) por opción, pintado por la vista
 * publicada en resources/views/vendor/filament-forms/components/toggle-buttons/index.blade.php.
 * ToggleButtons normales (sin ->avatars()) no se ven afectados.
 */
class AvatarToggleButtons extends ToggleButtons
{
    protected array | Closure | null $avatars = null;

    public function avatars(array | Closure | null $avatars): static
    {
        $this->avatars = $avatars;

        return $this;
    }

    public function getAvatarUrl(int | string $value): ?string
    {
        $avatars = $this->evaluate($this->avatars) ?? [];

        return $avatars[$value] ?? null;
    }
}
