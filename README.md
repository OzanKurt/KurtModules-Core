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
- `Kurt\Modules\Core\Concerns\AbortsInProduction` — trait for Artisan commands; `abortIfProduction()` blocks demo/destructive commands in production unless `--force` is passed. Lets `*:demo` commands drop their hand-rolled guards.
- `Kurt\Modules\Core\Concerns\HasEnumLabels` — trait giving string-backed enums a Title-Case `getLabel()` and a null `getColor()` default, so downstream enums can satisfy Filament's `HasLabel`/`HasColor` without Core requiring Filament.
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

## Command & enum helpers

Guard demo/destructive Artisan commands so they cannot run in production without `--force`:

```php
use Illuminate\Console\Command;
use Kurt\Modules\Core\Concerns\AbortsInProduction;

class SeedDemoCommand extends Command
{
    use AbortsInProduction;

    protected $signature = 'blog:demo {--force}';

    public function handle(): int
    {
        if ($this->abortIfProduction()) {
            return self::FAILURE;
        }

        // ... seed demo data ...

        return self::SUCCESS;
    }
}
```

Give a string-backed enum Filament-ready labels/colors without Core depending on Filament (the enum declares the contracts, the trait supplies the bodies):

```php
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Kurt\Modules\Core\Concerns\HasEnumLabels;

enum Status: string implements HasColor, HasLabel
{
    use HasEnumLabels;

    case Draft = 'draft';
    case InReview = 'in_review'; // getLabel() => "In Review"
    case Published = 'published';

    // Optional: override getColor() per case; getLabel() keeps the default.
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Published => 'success',
            default => null,
        };
    }
}
```

## License

MIT © Ozan Kurt
