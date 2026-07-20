<?php

declare(strict_types=1);

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Kurt\Modules\Core\Tests\Stubs\StubColoredStatus;
use Kurt\Modules\Core\Tests\Stubs\StubStatus;

it('derives a title-cased label from the case name', function () {
    expect(StubStatus::Draft->getLabel())->toBe('Draft');
    expect(StubStatus::InReview->getLabel())->toBe('In Review');
    expect(StubStatus::Published->getLabel())->toBe('Published');
});

it('returns a null color by default', function () {
    expect(StubStatus::Draft->getColor())->toBeNull();
});

it('satisfies Filament HasLabel and HasColor contracts', function () {
    expect(StubStatus::Draft)->toBeInstanceOf(HasLabel::class);
    expect(StubStatus::Draft)->toBeInstanceOf(HasColor::class);
});

it('lets the enum override getColor while keeping the default label', function () {
    expect(StubColoredStatus::Approved->getColor())->toBe('success');
    expect(StubColoredStatus::Pending->getColor())->toBe('warning');
    expect(StubColoredStatus::Approved->getLabel())->toBe('Approved');
});
