<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Concerns;

trait InteractsWithModuleConfig
{
    abstract protected function module(): string;

    protected function moduleConfig(string $key, mixed $default = null): mixed
    {
        return config("{$this->module()}.{$key}", $default);
    }
}
