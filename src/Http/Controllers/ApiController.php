<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;

/**
 * Base controller for module REST APIs.
 *
 * Provides a consistent success/error envelope and the standard
 * authorization/validation traits so module controllers stay thin over their
 * domain services.
 *
 * Envelope conventions:
 *   success: { "data": <payload>, "meta": <object, omitted when empty> }
 *   error:   { "message": <string>, "errors": <object> }
 *
 * The envelope shape mirrors Laravel API Resources, whose top-level key is also
 * `data`. To avoid a doubly-wrapped `{"data":{"data":...}}` payload, pass the
 * *result* of a resource (e.g. `UserResource::make($user)`) — never leave
 * `JsonResource::$wrap` at its `data` default and also call {@see respond()}.
 * These helpers add the single `data` wrapper; hand them the resource itself.
 */
abstract class ApiController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * Success envelope: `{ "data": ..., "meta": ... }`. The `meta` key is
     * omitted entirely when empty.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function respond(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * 201 Created success envelope.
     */
    protected function respondCreated(mixed $data): JsonResponse
    {
        return $this->respond($data, [], 201);
    }

    /**
     * 204 No Content, with a genuinely empty body (a bare `response()->json()`
     * would encode `null` as `{}`, which is invalid for a 204).
     */
    protected function respondNoContent(): JsonResponse
    {
        $response = new JsonResponse(null, 204);
        $response->setContent('');

        return $response;
    }

    /**
     * Envelope a length-aware paginator: the items become `data`, and
     * `meta.pagination` carries current_page / per_page / total / last_page.
     *
     * When a JsonResource class is given, each item is mapped through it so the
     * paginated payload matches the module's resource shape.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  class-string<JsonResource>|null  $resourceClass
     */
    protected function respondPaginated(LengthAwarePaginator $paginator, ?string $resourceClass = null): JsonResponse
    {
        $items = $paginator->items();

        $data = $resourceClass !== null
            ? $resourceClass::collection($items)
            : $items;

        return $this->respond($data, [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Error envelope: `{ "message": ..., "errors": ... }`. Defaults to 422 to
     * match Laravel's validation-error status.
     *
     * @param  array<string, mixed>  $errors
     */
    protected function fail(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
