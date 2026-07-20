<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Kurt\Modules\Core\Concerns\HasEnumLabels;

/**
 * Uses Core's trait for the method bodies while declaring Filament's contracts
 * in the (downstream) enum, mirroring how modules consume {@see HasEnumLabels}.
 */
enum StubStatus: string implements HasColor, HasLabel
{
    use HasEnumLabels;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
}
