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

it('fires the overridden filament hook through the package boot lifecycle', function () {
    FilamentVersion::override('5.1.3');

    // Register the provider the way Laravel does: this drives the full
    // register + boot lifecycle, so packageBooted() (not the test-only
    // dispatch wrapper) is what invokes the overridden registerFilamentV5.
    $provider = app()->register(new StubFilamentProvider(app()));

    expect($provider->fired)->toBe([5]);
});

it('does not double-register when a provider re-invokes registerFilament', function () {
    FilamentVersion::override('4.0.0');

    $provider = makeStubProvider();

    // Simulate a downstream provider whose packageBooted() calls
    // parent::packageBooted() (which wires Filament) and then also wires it
    // itself: the per-instance guard keeps it to a single registration.
    $provider->packageBooted();
    $provider->dispatchFilament();

    expect($provider->fired)->toBe([4]);
});
