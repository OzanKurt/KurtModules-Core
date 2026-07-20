<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Core\Tests\Stubs\StubFilamentProvider;

afterEach(function () {
    FilamentVersion::override(null);
});

function makeStubProvider(): StubFilamentProvider
{
    return new StubFilamentProvider(app());
}

it('dispatches to registerFilamentV3 when filament major is 3', function () {
    FilamentVersion::override('3.2.1');

    $provider = makeStubProvider();
    $provider->dispatchFilament();

    expect($provider->fired)->toBe([3]);
});

it('dispatches to registerFilamentV4 when filament major is 4', function () {
    FilamentVersion::override('4.0.0');

    $provider = makeStubProvider();
    $provider->dispatchFilament();

    expect($provider->fired)->toBe([4]);
});

it('dispatches to registerFilamentV5 when filament major is 5', function () {
    FilamentVersion::override('5.1.3');

    $provider = makeStubProvider();
    $provider->dispatchFilament();

    expect($provider->fired)->toBe([5]);
});

it('is a no-op when filament is not installed', function () {
    FilamentVersion::override(false);

    $provider = makeStubProvider();
    $provider->dispatchFilament();

    expect($provider->fired)->toBe([]);
});

it('is a no-op for an unsupported filament major', function () {
    FilamentVersion::override('2.9.9');

    $provider = makeStubProvider();
    $provider->dispatchFilament();

    expect($provider->fired)->toBe([]);
});
