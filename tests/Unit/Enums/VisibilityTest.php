<?php

declare(strict_types=1);

use Kurt\Modules\Core\Enums\Visibility;

it('exposes public/unlisted/private', function () {
    expect(Visibility::Public->value)->toBe('public');
    expect(Visibility::Unlisted->value)->toBe('unlisted');
    expect(Visibility::Private->value)->toBe('private');
});
