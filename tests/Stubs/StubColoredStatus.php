<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Kurt\Modules\Core\Concerns\HasEnumLabels;

/**
 * Overrides `getColor()` per case to show a downstream enum can keep the trait's
 * default label while supplying its own colors.
 */
enum StubColoredStatus: string implements HasColor, HasLabel
{
    use HasEnumLabels;

    case Pending = 'pending';
    case Approved = 'approved';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Approved => 'success',
            self::Pending => 'warning',
        };
    }
}
