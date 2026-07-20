<?php

declare(strict_types=1);

use Kurt\Modules\Core\Http\ApiRouteGroup;

it('builds default attributes when no config is set', function () {
    $attributes = ApiRouteGroup::attributes('demo');

    expect($attributes['prefix'])->toBe('api/demo')
        ->and($attributes['as'])->toBe('demo.api.')
        ->and($attributes['middleware'])->toBe(['api', 'throttle:demo-api']);
});

it('honours a custom prefix and middleware', function () {
    config()->set('demo.http.prefix', 'v1/demo');
    config()->set('demo.http.middleware', ['api', 'json.force']);

    $attributes = ApiRouteGroup::attributes('demo');

    expect($attributes['prefix'])->toBe('v1/demo')
        ->and($attributes['middleware'])->toBe(['api', 'json.force', 'throttle:demo-api']);
});

it('appends auth middleware for authenticated (write) groups', function () {
    $attributes = ApiRouteGroup::attributes('demo', authenticated: true);

    expect($attributes['middleware'])->toBe(['api', 'auth', 'throttle:demo-api']);
});

it('appends custom auth middleware before the throttle', function () {
    config()->set('demo.http.middleware', ['api']);
    config()->set('demo.http.auth_middleware', ['auth:sanctum', 'verified']);

    $attributes = ApiRouteGroup::attributes('demo', authenticated: true);

    expect($attributes['middleware'])->toBe(['api', 'auth:sanctum', 'verified', 'throttle:demo-api']);
});

it('accepts a single string middleware value', function () {
    config()->set('demo.http.middleware', 'api');

    $attributes = ApiRouteGroup::attributes('demo');

    expect($attributes['middleware'])->toBe(['api', 'throttle:demo-api']);
});

it('always throttles even with empty base middleware', function () {
    config()->set('demo.http.middleware', []);

    $attributes = ApiRouteGroup::attributes('demo');

    expect($attributes['middleware'])->toBe(['throttle:demo-api']);
});
