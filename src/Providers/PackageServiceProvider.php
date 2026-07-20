<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Providers;

use Kurt\Modules\Core\Support\FilamentVersion;
use Spatie\LaravelPackageTools\PackageServiceProvider as BasePackageServiceProvider;

abstract class PackageServiceProvider extends BasePackageServiceProvider
{
    /** Guards against wiring Filament resources more than once per instance. */
    private bool $filamentRegistered = false;

    /** Module short-name, e.g. 'blog'. */
    abstract protected function module(): string;

    /**
     * Wire Filament resources as part of the standard package boot lifecycle.
     *
     * Downstream providers only need to override registerFilamentV{3,4,5} — the
     * base handles dispatch. Providers that override packageBooted() themselves
     * should call parent::packageBooted(); calling registerFilament() again is
     * harmless because it is idempotent per instance.
     */
    public function packageBooted(): void
    {
        parent::packageBooted();

        $this->registerFilament();
    }

    /**
     * Build the fully-qualified, module-namespaced config key (e.g. "blog.title").
     *
     * This produces the dotted key string; reading the value at that key is the
     * job of the InteractsWithModuleConfig::moduleConfig() trait method, which
     * uses the same "{module}.{key}" convention. The two share the naming scheme
     * but sit at different layers: this returns the key, the trait reads it.
     */
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
        if ($this->filamentRegistered) {
            return;
        }

        $this->filamentRegistered = true;

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
