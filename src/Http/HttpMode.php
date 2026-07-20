<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Http;

/**
 * How much of a module's HTTP surface is exposed.
 *
 *   headless - nothing is registered; call the module's domain services yourself.
 *   api      - JSON REST endpoints are registered.
 *   ui       - everything in `api` plus any shipped HTML pages / Filament panels.
 *
 * Modules are safe-by-default: absent (or invalid) config resolves to Headless,
 * so no routes are registered until a consumer opts in.
 */
enum HttpMode: string
{
    case Headless = 'headless';
    case Api = 'api';
    case Ui = 'ui';

    /**
     * Resolve the mode for a module from `config("{slug}.http.mode")`.
     *
     * Unknown or non-string values resolve to {@see self::Headless} so a
     * mis-configured module never accidentally exposes its routes.
     */
    public static function forModule(string $slug): self
    {
        $value = config("{$slug}.http.mode", self::Headless->value);

        if (! is_string($value)) {
            return self::Headless;
        }

        return self::tryFrom($value) ?? self::Headless;
    }

    /**
     * Whether the JSON API surface should be registered. True for Api and Ui;
     * UI is a superset of API.
     */
    public function apiEnabled(): bool
    {
        return $this === self::Api || $this === self::Ui;
    }

    /**
     * Whether this mode is any of the given modes.
     */
    public function is(self ...$modes): bool
    {
        return in_array($this, $modes, true);
    }
}
