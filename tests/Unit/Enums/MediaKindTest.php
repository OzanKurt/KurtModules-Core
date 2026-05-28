<?php

declare(strict_types=1);

use Kurt\Modules\Core\Enums\MediaKind;

it('covers none/image/video/carousel/file/document/link', function () {
    expect(MediaKind::cases())->toHaveCount(7);
    expect(MediaKind::None->value)->toBe('none');
    expect(MediaKind::Image->value)->toBe('image');
    expect(MediaKind::Video->value)->toBe('video');
    expect(MediaKind::Carousel->value)->toBe('carousel');
    expect(MediaKind::File->value)->toBe('file');
    expect(MediaKind::Document->value)->toBe('document');
    expect(MediaKind::Link->value)->toBe('link');
});
