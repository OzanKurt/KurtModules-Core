<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

final class StubUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
