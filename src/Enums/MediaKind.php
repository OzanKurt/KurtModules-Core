<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Enums;

enum MediaKind: string
{
    case None = 'none';
    case Image = 'image';
    case Video = 'video';
    case Carousel = 'carousel';
    case File = 'file';
    case Document = 'document';
    case Link = 'link';
}
