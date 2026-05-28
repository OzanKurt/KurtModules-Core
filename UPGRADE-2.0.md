# Upgrade Guide — v0.x → v2.0

The 2.0 line is a clean rebuild. Backwards compatibility is **not** preserved.

## Composer rename

```diff
-"ozankurt/modules-core": "^0.4"
+"ozankurt/laravel-modules-core": "^2.0"
```

Update `composer.json`, run `composer update`.

## Config rename

`config('kurt_modules.*')` is gone. The new root key is `kurtmodules`:

```diff
-config('kurt_modules.user_model')
+config('kurtmodules.user_model')
```

## Removed traits

| v0.x trait | Replacement |
|---|---|
| `Kurt\Modules\Core\Traits\GetUserModelData` | `Kurt\Modules\Core\Concerns\ResolvesUser` (trait) + `Kurt\Modules\Core\Contracts\UserResolver` (service) |
| `Kurt\Modules\Core\Traits\GetCountFromRelation` | Use Laravel `withCount()` / `loadCount()`. |
| `Kurt\Modules\Core\Traits\HasLinks` | Removed — Blog-only concern. Replaced by Blog v2's `Support\RouteBuilder` (only when needed). |

## Removed utility

`Kurt\Modules\Core\Links` was a string-template URL builder for Blog v1. Removed. Blog v2 ships its own `RouteBuilder` (opt-in).

## New requirement

PHP 8.4 is now the minimum. Laravel 12 or 13 only.
