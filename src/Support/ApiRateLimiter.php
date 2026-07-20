<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Kurt\Modules\Core\Http\ApiRouteGroup;

/**
 * Registers the named rate limiter that a module's API routes throttle against.
 *
 * The limiter is named "{slug}-api" (referenced as `throttle:{slug}-api` in
 * {@see ApiRouteGroup}) and reads its budget from
 * `config("{slug}.http.rate_limit")` in "maxAttempts,decayMinutes" form
 * (default "60,1"). Requests are keyed by authenticated user id, falling back
 * to client IP for guests.
 */
final class ApiRateLimiter
{
    /**
     * Register the "{slug}-api" limiter. Safe to call whenever a module's API
     * surface boots; re-registering simply overwrites the callback.
     */
    public static function register(string $slug): void
    {
        [$maxAttempts, $decayMinutes] = self::parse(config("{$slug}.http.rate_limit", '60,1'));

        RateLimiter::for("{$slug}-api", function (Request $request) use ($maxAttempts, $decayMinutes): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown';

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by((string) $key);
        });
    }

    /**
     * Parse a "maxAttempts,decayMinutes" string into ints, falling back to
     * 60 attempts / 1 minute for any missing or non-numeric part.
     *
     * @return array{0: int, 1: int}
     */
    private static function parse(mixed $rateLimit): array
    {
        $parts = is_string($rateLimit) ? explode(',', $rateLimit) : [];

        $maxAttempts = isset($parts[0]) && is_numeric(trim($parts[0])) ? (int) trim($parts[0]) : 60;
        $decayMinutes = isset($parts[1]) && is_numeric(trim($parts[1])) ? (int) trim($parts[1]) : 1;

        return [max(1, $maxAttempts), max(1, $decayMinutes)];
    }
}
