<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Kurt\Modules\Core\Support\ApiRateLimiter;

it('registers a named limiter from config', function () {
    config()->set('demo.http.rate_limit', '10,2');

    ApiRateLimiter::register('demo');

    $limiter = RateLimiter::limiter('demo-api');
    expect($limiter)->not->toBeNull();

    $limit = $limiter(Request::create('/'));
    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(10)
        ->and($limit->decaySeconds)->toBe(120);
});

it('defaults to 60 attempts per minute when config is missing', function () {
    ApiRateLimiter::register('nodef');

    $limit = RateLimiter::limiter('nodef-api')(Request::create('/'));

    expect($limit->maxAttempts)->toBe(60)
        ->and($limit->decaySeconds)->toBe(60);
});

it('keys guests by ip address', function () {
    ApiRateLimiter::register('demo');

    $limit = RateLimiter::limiter('demo-api')(Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.0.0.9']));

    expect($limit->key)->toBe('10.0.0.9');
});

it('keys authenticated requests by user identifier', function () {
    ApiRateLimiter::register('demo');

    $request = Request::create('/');
    $request->setUserResolver(fn () => new class
    {
        public function getAuthIdentifier(): int
        {
            return 42;
        }
    });

    $limit = RateLimiter::limiter('demo-api')($request);

    expect($limit->key)->toBe('42');
});

it('falls back to defaults for a malformed rate_limit string', function () {
    config()->set('demo.http.rate_limit', 'garbage');

    ApiRateLimiter::register('demo');

    $limit = RateLimiter::limiter('demo-api')(Request::create('/'));

    expect($limit->maxAttempts)->toBe(60)
        ->and($limit->decaySeconds)->toBe(60);
});
