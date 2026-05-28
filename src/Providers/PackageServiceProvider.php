<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Providers;

use Kurt\Modules\Core\Support\FilamentVersion;
use Spatie\LaravelPackageTools\PackageServiceProvider as BasePackageServiceProvider;

abstract class PackageServiceProvider extends BasePackageServiceProvider
{
    /** Module short-name, e.g. 'blog'. */
    abstract protected function module(): string;

    final protected function configKey(string $key): string
    {
        return "{$this->module()}.{$key}";
    }

    /**
     * Hook to wire Filament resources. Concrete providers may override
     * registerFilamentV3 / V4 / V5 to attach the matching resource set.
     */
    final protected function registerFilament(): void
    {
        $major = FilamentVersion::major();

        match (true) {
            $major === 5 => $this->registerFilamentV5(),
            $major === 4 => $this->registerFilamentV4(),
            $major === 3 => $this->registerFilamentV3(),
            default => null,
        };
    }

    protected function registerFilamentV3(): void {}

    protected function registerFilamentV4(): void {}

    protected function registerFilamentV5(): void {}
}
