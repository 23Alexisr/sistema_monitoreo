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
    protected string|Closure|null $itemColor = null;

    protected bool $agruparPorCategoria = false;

    public function itemColor(string|Closure|null $color): static
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

    /**
     * Cuando está activo, la vista publicada del Repeater
     * (resources/views/vendor/filament-forms/components/repeater/index.blade.php)
     * reordena y agrupa los items por categoría/subcategoría efectiva de
     * su trabajo_maestro, con el mismo criterio que EjecutarChecklist.
     */
    public function agruparPorCategoria(bool $condition = true): static
    {
        $this->agruparPorCategoria = $condition;

        return $this;
    }

    public function debeAgruparPorCategoria(): bool
    {
        return $this->agruparPorCategoria;
    }
}
