<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface UserResolver
{
    /** Fully-qualified class name of the user model. */
    public function modelClass(): string;

    /**
     * Builder for the user model.
     *
     * @return Builder<Model>
     */
    public function newQuery(): Builder;

    /** Primary-key column name on the user model. */
    public function primaryKey(): string;

    /** Database table the user model maps to. */
    public function table(): string;
}
