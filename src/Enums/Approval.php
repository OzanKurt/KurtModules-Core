<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Enums;

enum Approval: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
