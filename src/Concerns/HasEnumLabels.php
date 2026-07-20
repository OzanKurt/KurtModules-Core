<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Concerns;

use Illuminate\Support\Str;

/**
 * Default `getLabel()` / `getColor()` behaviour for string-backed enums so that
 * downstream modules can satisfy Filament's `HasLabel` / `HasColor` contracts
 * without Core taking a hard dependency on Filament.
 *
 * The consuming enum declares the Filament contracts; this trait only supplies
 * the method bodies:
 *
 *     use Filament\Support\Contracts\HasColor;
 *     use Filament\Support\Contracts\HasLabel;
 *     use Kurt\Modules\Core\Concerns\HasEnumLabels;
 *
 *     enum Status: string implements HasColor, HasLabel
 *     {
 *         use HasEnumLabels;
 *
 *         case InReview = 'in_review';
 *         case Published = 'published';
 *     }
 *
 * `Status::InReview->getLabel()` yields "In Review". Override `getColor()` (or
 * `getLabel()`) per case in the enum when a static default is not enough.
 *
 * @phpstan-require-implements \UnitEnum
 */
trait HasEnumLabels
{
    /**
     * Human-readable label derived from the case name (e.g. `InReview` -> "In Review").
     */
    public function getLabel(): ?string
    {
        return Str::headline($this->name);
    }

    /**
     * Default color. Returns null so Filament falls back to its own default;
     * override per case in the consuming enum to assign colors.
     *
     * @return string|array<string>|null
     */
    public function getColor(): string|array|null
    {
        return null;
    }
}
