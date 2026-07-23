<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Contracts;

use Kurt\Modules\Core\Modules\ModuleManifest;

interface ModuleRegistry
{
    public function register(ModuleManifest $manifest): void;

    /** @return array<string, ModuleManifest> */
    public function all(): array;

    public function get(string $slug): ?ModuleManifest;

    public function has(string $slug): bool;
}
