<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal Eloquent model backing the HandlesApiQuery / pagination tests.
 *
 * @property int $id
 * @property string $name
 * @property string $status
 */
class StubApiRecord extends Model
{
    protected $table = 'stub_api_records';

    protected $guarded = [];

    public $timestamps = false;
}
