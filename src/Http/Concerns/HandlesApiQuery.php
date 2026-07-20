<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Query-string helpers for module REST index endpoints: sorting, filtering and
 * pagination driven by request params.
 *
 * Every helper is allow-list driven and never interpolates raw request input as
 * a SQL identifier: only column names the caller explicitly permits reach the
 * query builder, so unknown or hostile params are silently dropped rather than
 * trusted.
 */
trait HandlesApiQuery
{
    /**
     * Apply `?sort=field,-other` ordering.
     *
     * A leading `-` means descending. Only fields present in `$allowed` are
     * applied; everything else is dropped silently.
     *
     * @param  Builder<covariant Model>  $query
     * @param  array<int, string>  $allowed
     * @return Builder<covariant Model>
     */
    protected function applyApiSorts(Builder $query, Request $request, array $allowed): Builder
    {
        $sort = $request->query('sort');

        if (! is_string($sort) || $sort === '') {
            return $query;
        }

        foreach (explode(',', $sort) as $field) {
            $field = trim($field);
            $direction = 'asc';

            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = substr($field, 1);
            }

            if ($field === '' || ! in_array($field, $allowed, true)) {
                continue;
            }

            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Apply `?filter[field]=value` constraints.
     *
     * `$allowed` may be a plain list of exact-match fields (`['name', 'slug']`)
     * or an assoc map marking each field `'exact'` or `'like'`
     * (`['name' => 'like', 'status' => 'exact']`). `like` fields match
     * `%value%`; everything else is an exact match. Fields not in the allow-list
     * and non-scalar values are dropped silently.
     *
     * @param  Builder<covariant Model>  $query
     * @param  array<int|string, string>  $allowed
     * @return Builder<covariant Model>
     */
    protected function applyApiFilters(Builder $query, Request $request, array $allowed): Builder
    {
        $filters = $request->query('filter');

        if (! is_array($filters)) {
            return $query;
        }

        $modes = $this->normaliseFilterAllowList($allowed);

        foreach ($filters as $field => $value) {
            if (! is_string($field) || ! array_key_exists($field, $modes) || ! is_scalar($value)) {
                continue;
            }

            $value = (string) $value;

            if ($modes[$field] === 'like') {
                $query->where($field, 'like', '%'.$value.'%');
            } else {
                $query->where($field, '=', $value);
            }
        }

        return $query;
    }

    /**
     * Paginate with a clamped `?per_page=` (bounded to [1, $max], defaulting to
     * $default) and the standard `?page=` param.
     *
     * @param  Builder<covariant Model>  $query
     * @return LengthAwarePaginator<int, Model>
     */
    protected function apiPaginate(Builder $query, Request $request, int $default = 15, int $max = 100): LengthAwarePaginator
    {
        $perPage = $request->query('per_page');
        $perPage = is_numeric($perPage) ? (int) $perPage : $default;
        $perPage = max(1, min($perPage, $max));

        return $query->paginate($perPage);
    }

    /**
     * Normalise the filter allow-list into a `[field => 'exact'|'like']` map,
     * accepting either a plain list of field names or an assoc mode map.
     *
     * @param  array<int|string, string>  $allowed
     * @return array<string, string>
     */
    private function normaliseFilterAllowList(array $allowed): array
    {
        $modes = [];

        foreach ($allowed as $key => $value) {
            if (is_int($key)) {
                $modes[$value] = 'exact';

                continue;
            }

            $modes[$key] = $value === 'like' ? 'like' : 'exact';
        }

        return $modes;
    }
}
