<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Repeater;

/**
 * Repeater con una franja de color lateral por item (según categoría del
 * trabajo), pintada por la vista publicada en
 * resources/views/vendor/filament-forms/components/repeater/index.blade.php.
 */
class ChecklistItemRepeater extends Repeater
{
    protected string | Closure | null $itemColor = null;

    public function itemColor(string | Closure | null $color): static
    {
        $this->itemColor = $color;

        return $this;
    }

    public function getItemColor(string $uuid): ?string
    {
        return $this->evaluate($this->itemColor, [
            'state' => $this->getRawItemState($uuid),
            'uuid' => $uuid,
        ]);
    }
}
