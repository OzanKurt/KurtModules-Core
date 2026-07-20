<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Core\Tests\Stubs\StubApiRecord;
use Kurt\Modules\Core\Tests\Stubs\StubApiResource;

/**
 * Anonymous controller exposing the protected envelope helpers.
 */
function apiControllerHarness(): ApiController
{
    return new class extends ApiController
    {
        /** @param  array<string, mixed>  $meta */
        public function doRespond(mixed $data, array $meta = [], int $status = 200): JsonResponse
        {
            return $this->respond($data, $meta, $status);
        }

        public function doCreated(mixed $data): JsonResponse
        {
            return $this->respondCreated($data);
        }

        public function doNoContent(): JsonResponse
        {
            return $this->respondNoContent();
        }

        /**
         * @param  LengthAwarePaginator<int, mixed>  $paginator
         * @param  class-string<JsonResource>|null  $resourceClass
         */
        public function doPaginated(LengthAwarePaginator $paginator, ?string $resourceClass = null): JsonResponse
        {
            return $this->respondPaginated($paginator, $resourceClass);
        }

        /** @param  array<string, mixed>  $errors */
        public function doFail(string $message, int $status = 422, array $errors = []): JsonResponse
        {
            return $this->fail($message, $status, $errors);
        }
    };
}

it('wraps data in a data key and omits empty meta', function () {
    $response = apiControllerHarness()->doRespond(['id' => 1]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe(['data' => ['id' => 1]]);
});

it('includes meta when provided', function () {
    $response = apiControllerHarness()->doRespond(['id' => 1], ['total' => 5]);

    expect($response->getData(true))->toBe([
        'data' => ['id' => 1],
        'meta' => ['total' => 5],
    ]);
});

it('respondCreated returns 201', function () {
    $response = apiControllerHarness()->doCreated(['id' => 9]);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getData(true))->toBe(['data' => ['id' => 9]]);
});

it('respondNoContent returns 204 with an empty body', function () {
    $response = apiControllerHarness()->doNoContent();

    expect($response->getStatusCode())->toBe(204)
        ->and($response->getContent())->toBe('');
});

it('paginated envelope carries pagination meta', function () {
    $paginator = new LengthAwarePaginator([['id' => 1], ['id' => 2]], total: 12, perPage: 5, currentPage: 2);

    $response = apiControllerHarness()->doPaginated($paginator);

    expect($response->getData(true))->toBe([
        'data' => [['id' => 1], ['id' => 2]],
        'meta' => [
            'pagination' => [
                'current_page' => 2,
                'per_page' => 5,
                'total' => 12,
                'last_page' => 3,
            ],
        ],
    ]);
});

it('maps paginated items through a resource class', function () {
    $items = [
        new StubApiRecord(['id' => 1, 'name' => 'apple']),
        new StubApiRecord(['id' => 2, 'name' => 'banana']),
    ];
    $paginator = new LengthAwarePaginator($items, total: 2, perPage: 15, currentPage: 1);

    $response = apiControllerHarness()->doPaginated($paginator, StubApiResource::class);

    expect($response->getData(true)['data'])->toBe([
        ['id' => 1, 'name' => 'apple'],
        ['id' => 2, 'name' => 'banana'],
    ]);
});

it('fail returns the error envelope with a 422 default', function () {
    $response = apiControllerHarness()->doFail('Invalid.');

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe([
            'message' => 'Invalid.',
            'errors' => [],
        ]);
});

it('fail honours a custom status and errors', function () {
    $response = apiControllerHarness()->doFail('Nope.', 403, ['field' => ['bad']]);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe([
            'message' => 'Nope.',
            'errors' => ['field' => ['bad']],
        ]);
});
