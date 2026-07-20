<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class StubFilamentProvider extends PackageServiceProvider
{
    /** @var list<int> Major versions whose hook fired, in order. */
    public array $fired = [];

    public function configurePackage(Package $package): void
    {
        $package->name('stub');
    }

    protected function module(): string
    {
        return 'stub';
    }

    /** Expose the protected dispatch entry point for testing. */
    public function dispatchFilament(): void
    {
        $this->registerFilament();
    }

    protected function registerFilamentV3(): void
    {
        $this->fired[] = 3;
    }

    protected function registerFilamentV4(): void
    {
        $this->fired[] = 4;
    }

    protected function registerFilamentV5(): void
    {
        $this->fired[] = 5;
    }
}
