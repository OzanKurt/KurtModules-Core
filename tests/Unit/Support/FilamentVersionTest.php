<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;

afterEach(function () {
    FilamentVersion::override(null);
});

it('returns null when filament is not installed', function () {
    FilamentVersion::override(false);

    expect(FilamentVersion::major())->toBeNull();
    expect(FilamentVersion::isAtLeast(3))->toBeFalse();
    expect(FilamentVersion::isExactly(3))->toBeFalse();
});

it('extracts major from semver string', function () {
    FilamentVersion::override('3.2.1');

    expect(FilamentVersion::major())->toBe(3);
    expect(FilamentVersion::isAtLeast(3))->toBeTrue();
    expect(FilamentVersion::isExactly(3))->toBeTrue();
    expect(FilamentVersion::isAtLeast(4))->toBeFalse();
});

it('handles prefixed versions like v4.0.0-beta', function () {
    FilamentVersion::override('v4.0.0-beta.2');

    expect(FilamentVersion::major())->toBe(4);
});
