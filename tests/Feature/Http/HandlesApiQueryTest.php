<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Tests\Stubs\StubApiRecord;

/**
 * Exposes the protected HandlesApiQuery helpers for assertion.
 */
function apiQueryHarness(): object
{
    return new class
    {
        use HandlesApiQuery;

        /**
         * @param  Builder<StubApiRecord>  $query
         * @param  array<int, string>  $allowed
         * @return Builder<StubApiRecord>
         */
        public function sorts(Builder $query, Request $request, array $allowed): Builder
        {
            return $this->applyApiSorts($query, $request, $allowed);
        }

        /**
         * @param  Builder<StubApiRecord>  $query
         * @param  array<int|string, string>  $allowed
         * @return Builder<StubApiRecord>
         */
        public function filters(Builder $query, Request $request, array $allowed): Builder
        {
            return $this->applyApiFilters($query, $request, $allowed);
        }

        /**
         * @param  Builder<StubApiRecord>  $query
         */
        public function paginate(Builder $query, Request $request, int $default = 15, int $max = 100): mixed
        {
            return $this->apiPaginate($query, $request, $default, $max);
        }
    };
}

beforeEach(function () {
    Schema::create('stub_api_records', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
    });

    foreach (['banana', 'apple', 'cherry'] as $i => $name) {
        StubApiRecord::create(['name' => $name, 'status' => $i === 0 ? 'draft' : 'active']);
    }
});

afterEach(function () {
    Schema::dropIfExists('stub_api_records');
});

it('applies an ascending sort on an allow-listed field', function () {
    $request = Request::create('/', 'GET', ['sort' => 'name']);

    $names = apiQueryHarness()->sorts(StubApiRecord::query(), $request, ['name'])->pluck('name')->all();

    expect($names)->toBe(['apple', 'banana', 'cherry']);
});

it('applies a descending sort with a leading minus', function () {
    $request = Request::create('/', 'GET', ['sort' => '-name']);

    $names = apiQueryHarness()->sorts(StubApiRecord::query(), $request, ['name'])->pluck('name')->all();

    expect($names)->toBe(['cherry', 'banana', 'apple']);
});

it('drops sort fields that are not allow-listed', function () {
    $request = Request::create('/', 'GET', ['sort' => 'name,-secret']);

    $query = apiQueryHarness()->sorts(StubApiRecord::query(), $request, ['name']);

    // Only the allow-listed `name` order should reach the query.
    expect($query->getQuery()->orders)->toBe([
        ['column' => 'name', 'direction' => 'asc'],
    ]);
});

it('is a no-op when no sort param is present', function () {
    $query = apiQueryHarness()->sorts(StubApiRecord::query(), Request::create('/'), ['name']);

    expect($query->getQuery()->orders)->toBeNull();
});

it('applies an exact filter on an allow-listed field', function () {
    $request = Request::create('/', 'GET', ['filter' => ['status' => 'draft']]);

    $records = apiQueryHarness()->filters(StubApiRecord::query(), $request, ['status'])->pluck('name')->all();

    expect($records)->toBe(['banana']);
});

it('applies a like filter when the field is marked like', function () {
    $request = Request::create('/', 'GET', ['filter' => ['name' => 'err']]);

    $records = apiQueryHarness()->filters(StubApiRecord::query(), $request, ['name' => 'like'])->pluck('name')->all();

    expect($records)->toBe(['cherry']);
});

it('drops filters that are not allow-listed', function () {
    $request = Request::create('/', 'GET', ['filter' => ['status' => 'draft', 'name' => 'apple']]);

    // Only `status` is permitted; the `name` constraint must be ignored.
    $records = apiQueryHarness()->filters(StubApiRecord::query(), $request, ['status'])->pluck('name')->all();

    expect($records)->toBe(['banana']);
});

it('ignores non-scalar filter values', function () {
    $request = Request::create('/', 'GET', ['filter' => ['status' => ['draft']]]);

    $records = apiQueryHarness()->filters(StubApiRecord::query(), $request, ['status'])->pluck('name')->all();

    expect($records)->toHaveCount(3);
});

it('paginates with the default per_page', function () {
    $paginator = apiQueryHarness()->paginate(StubApiRecord::query(), Request::create('/'), default: 2);

    expect($paginator->perPage())->toBe(2)
        ->and($paginator->total())->toBe(3)
        ->and($paginator->lastPage())->toBe(2);
});

it('clamps per_page to the max', function () {
    $request = Request::create('/', 'GET', ['per_page' => '999']);

    $paginator = apiQueryHarness()->paginate(StubApiRecord::query(), $request, max: 25);

    expect($paginator->perPage())->toBe(25);
});

it('clamps per_page to a minimum of one', function () {
    $request = Request::create('/', 'GET', ['per_page' => '0']);

    $paginator = apiQueryHarness()->paginate(StubApiRecord::query(), $request);

    expect($paginator->perPage())->toBe(1);
});

it('falls back to the default for a non-numeric per_page', function () {
    $request = Request::create('/', 'GET', ['per_page' => 'lots']);

    $paginator = apiQueryHarness()->paginate(StubApiRecord::query(), $request, default: 7);

    expect($paginator->perPage())->toBe(7);
});
