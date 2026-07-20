# laravel-modules-core

Shared bootstrap kit for [KurtModules](https://github.com/ozankurt) Laravel packages.

## Requirements

- PHP 8.3+
- Laravel 12.x
- (Optional) Filament 3, 4, or 5

## Installation

```bash
composer require ozankurt/laravel-modules-core
```

## What it provides

- `Kurt\Modules\Core\Providers\PackageServiceProvider` — abstract base every kurtmodules service provider extends. Wraps `spatie/laravel-package-tools` and dispatches to `registerFilamentV{3,4,5}` based on the installed Filament major.
- `Kurt\Modules\Core\Contracts\UserResolver` (+ `ConfigUserResolver`) — resolves the consumer's user model via `kurtmodules.user_model` config or `auth.providers.users.model` fallback.
- `Kurt\Modules\Core\Concerns\ResolvesUser` — trait that gives module models a `userBelongsTo()` helper.
- `Kurt\Modules\Core\Concerns\InteractsWithModuleConfig` — sugar for `config("{module}.key")` access.
- `Kurt\Modules\Core\Support\FilamentVersion` — `::major()`, `::isAtLeast()`, `::isExactly()`.
- `Kurt\Modules\Core\Enums\{Approval,MediaKind,Visibility}` — generic cross-module enums.
- `Kurt\Modules\Core\Testing\PackageTestCase` — Testbench-backed base test case with an in-memory `users` table.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="kurtmodules-config"
```

```php
return [
    'user_model' => env('KURTMODULES_USER_MODEL'),
];
```

## License

MIT © Ozan Kurt
