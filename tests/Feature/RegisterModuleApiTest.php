<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

/**
 * Provider exposing the protected registerModuleApi() hook for the fixed
 * `demo` module slug.
 */
function moduleApiProvider(): PackageServiceProvider
{
    return new class(app()) extends PackageServiceProvider
    {
        protected function module(): string
        {
            return 'demo';
        }

        public function configurePackage(Package $package): void
        {
            $package->name('demo');
        }

        public function callRegisterModuleApi(string $file): void
        {
            $this->registerModuleApi($file);
        }
    };
}

$routesFile = fn () => __DIR__.'/../Stubs/stub-api-routes.php';

it('is a no-op in headless mode', function () use ($routesFile) {
    moduleApiProvider()->callRegisterModuleApi($routesFile());
    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('demo.api.ping'))->toBeFalse()
        ->and(RateLimiter::limiter('demo-api'))->toBeNull();
});

it('registers the throttle limiter and routes in api mode', function () use ($routesFile) {
    config()->set('demo.http.mode', 'api');

    moduleApiProvider()->callRegisterModuleApi($routesFile());
    // The framework refreshes name lookups after the boot phase; do it here so
    // the manually-invoked hook is observable in-test.
    Route::getRoutes()->refreshNameLookups();

    expect(RateLimiter::limiter('demo-api'))->not->toBeNull()
        ->and(Route::has('demo.api.ping'))->toBeTrue();

    $route = Route::getRoutes()->getByName('demo.api.ping');
    expect($route->uri())->toBe('api/demo/ping')
        ->and($route->gatherMiddleware())->toBe(['api', 'throttle:demo-api']);
});

it('enables the api surface in ui mode', function () use ($routesFile) {
    config()->set('demo.http.mode', 'ui');

    moduleApiProvider()->callRegisterModuleApi($routesFile());

    // UI is a superset of API, so the module rate limiter is registered just as
    // it is for api mode (route file loading shares the same code path).
    expect(RateLimiter::limiter('demo-api'))->not->toBeNull();
});
