<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Enums;

enum Visibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
