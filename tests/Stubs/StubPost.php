<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Core\Contracts\UserResolver;

final class StubPost extends Model
{
    use ResolvesUser;

    protected $table = 'posts';

    protected $guarded = [];

    public $timestamps = false;

    /** Expose the trait relation for testing. */
    public function user(string $foreignKey = 'user_id'): BelongsTo
    {
        return $this->userBelongsTo($foreignKey);
    }

    /** Expose the resolved resolver for testing. */
    public function resolver(): UserResolver
    {
        return $this->userResolver();
    }
}
