<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Core\Contracts\UserResolver;
use RuntimeException;

final class ConfigUserResolver implements UserResolver
{
    public function __construct(private readonly Repository $config) {}

    public function modelClass(): string
    {
        $class = $this->config->get('kurtmodules.user_model')
            ?: $this->config->get('auth.providers.users.model');

        if (! is_string($class) || $class === '') {
            throw new RuntimeException('No user model configured. Set kurtmodules.user_model or auth.providers.users.model.');
        }

        return $class;
    }

    /**
     * @return Builder<Model>
     */
    public function newQuery(): Builder
    {
        return $this->modelInstance()->newQuery();
    }

    public function primaryKey(): string
    {
        return $this->modelInstance()->getKeyName();
    }

    public function table(): string
    {
        return $this->modelInstance()->getTable();
    }

    private function modelInstance(): Model
    {
        $class = $this->modelClass();

        if (! class_exists($class)) {
            throw new RuntimeException(sprintf(
                'Configured user model [%s] class not found.',
                $class,
            ));
        }

        if (! is_subclass_of($class, Model::class)) {
            throw new RuntimeException(sprintf(
                'Configured user model [%s] must extend %s.',
                $class,
                Model::class,
            ));
        }

        return new $class;
    }
}
