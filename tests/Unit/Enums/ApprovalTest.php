<?php

declare(strict_types=1);

use Kurt\Modules\Core\Enums\Approval;

it('exposes pending, approved and rejected cases with stable string values', function () {
    expect(Approval::Pending->value)->toBe('pending');
    expect(Approval::Approved->value)->toBe('approved');
    expect(Approval::Rejected->value)->toBe('rejected');
});

it('is constructible from value', function () {
    expect(Approval::from('approved'))->toBe(Approval::Approved);
});
