<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Http;

use Kurt\Modules\Core\Support\ApiRateLimiter;

/**
 * Builds the `Route::group(...)` attribute array for a module's REST API from
 * its per-module `http` config block, applying safe defaults when keys are
 * absent:
 *
 *   {slug}.http.prefix          default "api/{slug}"
 *   {slug}.http.middleware      default ['api']
 *   {slug}.http.auth_middleware default ['auth']  (appended to write routes)
 *
 * Read (public) routes get the base middleware plus the module throttle;
 * write routes additionally get the auth middleware. Every group is throttled
 * by the named limiter "{slug}-api" (see {@see ApiRateLimiter}).
 */
final class ApiRouteGroup
{
    /**
     * @return array{prefix: string, middleware: array<int, string>, as: string}
     */
    public static function attributes(string $slug, bool $authenticated = false): array
    {
        return [
            'prefix' => self::prefix($slug),
            'middleware' => self::middleware($slug, $authenticated),
            'as' => "{$slug}.api.",
        ];
    }

    private static function prefix(string $slug): string
    {
        $prefix = config("{$slug}.http.prefix", "api/{$slug}");

        return is_string($prefix) ? $prefix : "api/{$slug}";
    }

    /**
     * @return array<int, string>
     */
    private static function middleware(string $slug, bool $authenticated): array
    {
        $middleware = self::stringList(config("{$slug}.http.middleware", ['api']));

        if ($authenticated) {
            $middleware = array_merge(
                $middleware,
                self::stringList(config("{$slug}.http.auth_middleware", ['auth'])),
            );
        }

        // Throttle last so it runs after any auth middleware has resolved the
        // user the limiter keys on.
        $middleware[] = "throttle:{$slug}-api";

        return array_values($middleware);
    }

    /**
     * Normalise a config value into a list of middleware strings. Accepts a
     * single string or an array; anything else falls back to an empty list.
     *
     * @return array<int, string>
     */
    private static function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
