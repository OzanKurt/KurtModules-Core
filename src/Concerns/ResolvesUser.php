<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Contracts\UserResolver;

trait ResolvesUser
{
    protected function userResolver(): UserResolver
    {
        return app(UserResolver::class);
    }

    /**
     * Return a BelongsTo to the consumer-supplied user model.
     */
    protected function userBelongsTo(string $foreignKey = 'user_id'): BelongsTo
    {
        $resolver = $this->userResolver();

        return $this->belongsTo(
            $resolver->modelClass(),
            $foreignKey,
            $resolver->primaryKey(),
        );
    }
}
