<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Support;

use Composer\InstalledVersions;

final class FilamentVersion
{
    /** Test override. `null` = use composer. `false` = simulate "not installed". Anything else = forced version string. */
    private static null|false|string $override = null;

    public static function major(): ?int
    {
        $version = self::resolve();

        if ($version === null) {
            return null;
        }

        if (! preg_match('/(\d+)/', $version, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public static function isAtLeast(int $major): bool
    {
        $current = self::major();

        return $current !== null && $current >= $major;
    }

    public static function isExactly(int $major): bool
    {
        return self::major() === $major;
    }

    /** @internal Test hook. */
    public static function override(null|false|string $value): void
    {
        self::$override = $value;
    }

    private static function resolve(): ?string
    {
        if (self::$override === false) {
            return null;
        }

        if (is_string(self::$override)) {
            return self::$override;
        }

        if (! class_exists(InstalledVersions::class)) {
            return null;
        }

        try {
            return InstalledVersions::getVersion('filament/filament');
        } catch (\Throwable) {
            return null;
        }
    }
}
