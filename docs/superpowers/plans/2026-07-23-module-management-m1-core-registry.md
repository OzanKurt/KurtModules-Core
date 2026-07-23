# Module Management M1 - Core Manifest & Registry - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a module self-declaration mechanism to `laravel-modules-core`: a `ModuleManifest` value object each module declares, collected at boot into an in-memory `ModuleRegistry`. No DB, no API - that is M2/M3.

**Architecture:** A fluent `ModuleManifest` value object (slug, name, version, description, dependencies, feature defaults, setting defaults, enabled-by-default). A `ModuleRegistry` contract with an in-memory `Registry` implementation, bound as a singleton by `CoreServiceProvider`. The base `PackageServiceProvider` gains a `moduleManifest()` hook (default `null`); during `packageBooted()` it registers a non-null manifest into the registry. Modules opt in by overriding `moduleManifest()`.

**Tech Stack:** PHP 8.4, Laravel 12 (`illuminate/*` ^12), `spatie/laravel-package-tools`, Pest 3 + Orchestra Testbench, on top of the existing core package.

## Global Constraints

- Package: `ozankurt/laravel-modules-core`, namespace `Kurt\Modules\Core\`, repo `OzanKurt/laravel-modules-core`.
- PHP `^8.3` in composer, but this machine's default `php` is 8.3 while tests must run on 8.4. Use the Laragon 8.4 binary explicitly (see Toolchain).
- `declare(strict_types=1);` at the top of every PHP file (matches existing core files).
- Commits follow EpicAlgorithms git guidelines: **no AI attribution** in messages.
- No DB, no HTTP, no Filament in M1 - pure in-memory registry.

## Toolchain (this machine)

`PHP84` = `C:/laragon/bin/php/php-8.4.5-nts-Win32-vs17-x64/php.exe`

- Tests: `"$PHP84" vendor/bin/pest`
- Single file: `"$PHP84" vendor/bin/pest tests/Unit/Modules/ModuleManifestTest.php`
- Stan: `"$PHP84" vendor/bin/phpstan analyse --memory-limit=2G`

Work in the local clone `D:\Code\Projects\KurtModules-Core` (remote is `laravel-modules-core`).

## File Structure

- `src/Modules/ModuleManifest.php` - fluent value object; module's self-declaration.
- `src/Contracts/ModuleRegistry.php` - registry contract (register/all/get/has).
- `src/Modules/Registry.php` - in-memory `ModuleRegistry` implementation.
- `src/Providers/PackageServiceProvider.php` - MODIFY: add `moduleManifest()` hook + register manifest in `packageBooted()`.
- `src/Providers/CoreServiceProvider.php` - MODIFY: bind `ModuleRegistry` singleton.
- `tests/Unit/Modules/ModuleManifestTest.php`, `tests/Unit/Modules/RegistryTest.php`, `tests/Feature/Modules/ModuleManifestRegistrationTest.php`.

---

## Task 1: ModuleManifest value object

**Files:**
- Create: `src/Modules/ModuleManifest.php`
- Test: `tests/Unit/Modules/ModuleManifestTest.php`

**Interfaces:**
- Produces: `Kurt\Modules\Core\Modules\ModuleManifest` with static `make(string $slug): self`; fluent setters `name(string): self`, `version(string): self`, `description(string): self`, `enabledByDefault(bool = true): self`, `dependsOn(string ...$slugs): self`, `feature(string $key, bool $default = false): self`, `setting(string $key, mixed $default = null, string $type = 'string'): self`; getters `slug(): string`, `getName(): string`, `getVersion(): ?string`, `getDescription(): ?string`, `isEnabledByDefault(): bool`, `dependencies(): array`, `features(): array<string,bool>`, `hasFeature(string): bool`, `featureDefault(string): bool`, `settings(): array<string,array{default:mixed,type:string}>`, `hasSetting(string): bool`, `settingDefault(string): mixed`, `settingType(string): ?string`.

- [ ] **Step 1: Write the failing test `tests/Unit/Modules/ModuleManifestTest.php`**

```php
<?php

declare(strict_types=1);

use Kurt\Modules\Core\Modules\ModuleManifest;

it('builds a manifest with defaults', function () {
    $m = ModuleManifest::make('blog');

    expect($m->slug())->toBe('blog')
        ->and($m->getName())->toBe('blog')            // name defaults to slug
        ->and($m->getVersion())->toBeNull()
        ->and($m->getDescription())->toBeNull()
        ->and($m->isEnabledByDefault())->toBeTrue()   // enabled unless opted out
        ->and($m->dependencies())->toBe([])
        ->and($m->features())->toBe([])
        ->and($m->settings())->toBe([]);
});

it('builds a fully populated manifest fluently', function () {
    $m = ModuleManifest::make('blog')
        ->name('Blog')
        ->version('1.0.0')
        ->description('Blog module')
        ->enabledByDefault(false)
        ->dependsOn('interactions', 'media-library')
        ->feature('comments', default: true)
        ->feature('reactions')
        ->setting('posts_per_page', default: 15, type: 'int');

    expect($m->getName())->toBe('Blog')
        ->and($m->getVersion())->toBe('1.0.0')
        ->and($m->getDescription())->toBe('Blog module')
        ->and($m->isEnabledByDefault())->toBeFalse()
        ->and($m->dependencies())->toBe(['interactions', 'media-library'])
        ->and($m->features())->toBe(['comments' => true, 'reactions' => false])
        ->and($m->hasFeature('comments'))->toBeTrue()
        ->and($m->featureDefault('comments'))->toBeTrue()
        ->and($m->featureDefault('reactions'))->toBeFalse()
        ->and($m->hasFeature('missing'))->toBeFalse()
        ->and($m->featureDefault('missing'))->toBeFalse()
        ->and($m->settings())->toBe(['posts_per_page' => ['default' => 15, 'type' => 'int']])
        ->and($m->hasSetting('posts_per_page'))->toBeTrue()
        ->and($m->settingDefault('posts_per_page'))->toBe(15)
        ->and($m->settingType('posts_per_page'))->toBe('int')
        ->and($m->hasSetting('missing'))->toBeFalse()
        ->and($m->settingDefault('missing'))->toBeNull()
        ->and($m->settingType('missing'))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"$PHP84" vendor/bin/pest tests/Unit/Modules/ModuleManifestTest.php`
Expected: FAIL - `Class "Kurt\Modules\Core\Modules\ModuleManifest" not found`.

- [ ] **Step 3: Write `src/Modules/ModuleManifest.php`**

```php
<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Modules;

/**
 * A module's self-declaration: identity, dependencies, and the feature/setting
 * keys it exposes with their default values. Collected into the
 * {@see \Kurt\Modules\Core\Contracts\ModuleRegistry} at boot. Pure value object -
 * holds no runtime state and touches no DB.
 */
final class ModuleManifest
{
    private string $name;

    private ?string $version = null;

    private ?string $description = null;

    private bool $enabledByDefault = true;

    /** @var list<string> */
    private array $dependencies = [];

    /** @var array<string, bool> */
    private array $features = [];

    /** @var array<string, array{default: mixed, type: string}> */
    private array $settings = [];

    private function __construct(private readonly string $slug)
    {
        $this->name = $slug;
    }

    public static function make(string $slug): self
    {
        return new self($slug);
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function version(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function enabledByDefault(bool $enabled = true): self
    {
        $this->enabledByDefault = $enabled;

        return $this;
    }

    public function dependsOn(string ...$slugs): self
    {
        foreach ($slugs as $slug) {
            $this->dependencies[] = $slug;
        }

        return $this;
    }

    public function feature(string $key, bool $default = false): self
    {
        $this->features[$key] = $default;

        return $this;
    }

    public function setting(string $key, mixed $default = null, string $type = 'string'): self
    {
        $this->settings[$key] = ['default' => $default, 'type' => $type];

        return $this;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isEnabledByDefault(): bool
    {
        return $this->enabledByDefault;
    }

    /** @return list<string> */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /** @return array<string, bool> */
    public function features(): array
    {
        return $this->features;
    }

    public function hasFeature(string $key): bool
    {
        return array_key_exists($key, $this->features);
    }

    public function featureDefault(string $key): bool
    {
        return $this->features[$key] ?? false;
    }

    /** @return array<string, array{default: mixed, type: string}> */
    public function settings(): array
    {
        return $this->settings;
    }

    public function hasSetting(string $key): bool
    {
        return array_key_exists($key, $this->settings);
    }

    public function settingDefault(string $key): mixed
    {
        return $this->settings[$key]['default'] ?? null;
    }

    public function settingType(string $key): ?string
    {
        return $this->settings[$key]['type'] ?? null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `"$PHP84" vendor/bin/pest tests/Unit/Modules/ModuleManifestTest.php`
Expected: PASS (2 passed).

- [ ] **Step 5: Commit**

```bash
git add src/Modules/ModuleManifest.php tests/Unit/Modules/ModuleManifestTest.php
git commit -m "feat: add ModuleManifest value object"
```

---

## Task 2: ModuleRegistry contract + in-memory Registry

**Files:**
- Create: `src/Contracts/ModuleRegistry.php`
- Create: `src/Modules/Registry.php`
- Test: `tests/Unit/Modules/RegistryTest.php`

**Interfaces:**
- Consumes: `ModuleManifest` from Task 1.
- Produces: interface `Kurt\Modules\Core\Contracts\ModuleRegistry` with `register(ModuleManifest): void`, `all(): array<string, ModuleManifest>`, `get(string $slug): ?ModuleManifest`, `has(string $slug): bool`; class `Kurt\Modules\Core\Modules\Registry` implementing it (keyed by slug; `register` overwrites a same-slug entry).

- [ ] **Step 1: Write the failing test `tests/Unit/Modules/RegistryTest.php`**

```php
<?php

declare(strict_types=1);

use Kurt\Modules\Core\Contracts\ModuleRegistry;
use Kurt\Modules\Core\Modules\ModuleManifest;
use Kurt\Modules\Core\Modules\Registry;

it('registers and retrieves manifests keyed by slug', function () {
    $registry = new Registry();
    expect($registry)->toBeInstanceOf(ModuleRegistry::class);

    $blog = ModuleManifest::make('blog');
    $chat = ModuleManifest::make('chat');
    $registry->register($blog);
    $registry->register($chat);

    expect($registry->has('blog'))->toBeTrue()
        ->and($registry->has('chat'))->toBeTrue()
        ->and($registry->has('missing'))->toBeFalse()
        ->and($registry->get('blog'))->toBe($blog)
        ->and($registry->get('missing'))->toBeNull()
        ->and(array_keys($registry->all()))->toBe(['blog', 'chat']);
});

it('overwrites a manifest registered under the same slug', function () {
    $registry = new Registry();
    $first = ModuleManifest::make('blog')->version('1.0.0');
    $second = ModuleManifest::make('blog')->version('2.0.0');

    $registry->register($first);
    $registry->register($second);

    expect($registry->all())->toHaveCount(1)
        ->and($registry->get('blog'))->toBe($second);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"$PHP84" vendor/bin/pest tests/Unit/Modules/RegistryTest.php`
Expected: FAIL - `Interface "Kurt\Modules\Core\Contracts\ModuleRegistry" not found`.

- [ ] **Step 3: Write `src/Contracts/ModuleRegistry.php`**

```php
<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Contracts;

use Kurt\Modules\Core\Modules\ModuleManifest;

interface ModuleRegistry
{
    public function register(ModuleManifest $manifest): void;

    /** @return array<string, ModuleManifest> */
    public function all(): array;

    public function get(string $slug): ?ModuleManifest;

    public function has(string $slug): bool;
}
```

- [ ] **Step 4: Write `src/Modules/Registry.php`**

```php
<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Modules;

use Kurt\Modules\Core\Contracts\ModuleRegistry;

/**
 * In-memory module registry. Populated once per request during package boot
 * (see {@see \Kurt\Modules\Core\Providers\PackageServiceProvider}); a later
 * same-slug registration overwrites the earlier one.
 */
final class Registry implements ModuleRegistry
{
    /** @var array<string, ModuleManifest> */
    private array $modules = [];

    public function register(ModuleManifest $manifest): void
    {
        $this->modules[$manifest->slug()] = $manifest;
    }

    /** @return array<string, ModuleManifest> */
    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $slug): ?ModuleManifest
    {
        return $this->modules[$slug] ?? null;
    }

    public function has(string $slug): bool
    {
        return isset($this->modules[$slug]);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"$PHP84" vendor/bin/pest tests/Unit/Modules/RegistryTest.php`
Expected: PASS (2 passed).

- [ ] **Step 6: Commit**

```bash
git add src/Contracts/ModuleRegistry.php src/Modules/Registry.php tests/Unit/Modules/RegistryTest.php
git commit -m "feat: add ModuleRegistry contract and in-memory Registry"
```

---

## Task 3: Wire manifest declaration into the provider lifecycle

**Files:**
- Modify: `src/Providers/CoreServiceProvider.php` (bind `ModuleRegistry` singleton)
- Modify: `src/Providers/PackageServiceProvider.php` (add `moduleManifest()` hook + register in `packageBooted()`)
- Test: `tests/Feature/Modules/ModuleManifestRegistrationTest.php`

**Interfaces:**
- Consumes: `ModuleRegistry`, `Registry`, `ModuleManifest` from Tasks 1-2.
- Produces: `ModuleRegistry` resolvable from the container as a singleton; base `PackageServiceProvider::moduleManifest(): ?ModuleManifest` (default `null`) that subclasses override to self-register. Subclasses overriding `packageBooted()` must call `parent::packageBooted()` (already the documented convention).

- [ ] **Step 1: Write the failing test `tests/Feature/Modules/ModuleManifestRegistrationTest.php`**

```php
<?php

declare(strict_types=1);

use Kurt\Modules\Core\Contracts\ModuleRegistry;
use Kurt\Modules\Core\Modules\ModuleManifest;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Spatie\LaravelPackageTools\Package;

/** A throwaway module provider that declares a manifest. */
final class DemoModuleServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'demo';
    }

    public function configurePackage(Package $package): void
    {
        $package->name('demo');
    }

    protected function moduleManifest(): ?ModuleManifest
    {
        return ModuleManifest::make('demo')->name('Demo')->feature('thing', default: true);
    }
}

final class ModuleManifestRegistrationTest extends PackageTestCase
{
    /** @param  \Illuminate\Foundation\Application  $app */
    protected function modulePackageProviders($app): array
    {
        return [DemoModuleServiceProvider::class];
    }

    public function test_core_binds_the_registry_as_a_singleton(): void
    {
        $this->assertInstanceOf(ModuleRegistry::class, $this->app->make(ModuleRegistry::class));
        $this->assertSame($this->app->make(ModuleRegistry::class), $this->app->make(ModuleRegistry::class));
    }

    public function test_a_module_provider_declares_its_manifest_into_the_registry(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $this->assertTrue($registry->has('demo'));
        $this->assertSame('Demo', $registry->get('demo')->getName());
        $this->assertTrue($registry->get('demo')->featureDefault('thing'));
    }

    public function test_a_provider_without_a_manifest_registers_nothing(): void
    {
        // Core itself declares no manifest; only 'demo' should be present.
        $this->assertSame(['demo'], array_keys($this->app->make(ModuleRegistry::class)->all()));
    }
}
```

Note: this test uses xUnit-style (`PackageTestCase` subclass) rather than Pest closures because it needs `modulePackageProviders()` to register the demo provider - matching how the core package's existing feature tests wire module providers.

- [ ] **Step 2: Run test to verify it fails**

Run: `"$PHP84" vendor/bin/pest tests/Feature/Modules/ModuleManifestRegistrationTest.php`
Expected: FAIL - `ModuleRegistry` is not bound (`BindingResolutionException`) / `moduleManifest()` undefined.

- [ ] **Step 3: Bind the registry singleton in `CoreServiceProvider`**

In `src/Providers/CoreServiceProvider.php`, add the imports and extend `packageRegistered()`:

```php
use Kurt\Modules\Core\Contracts\ModuleRegistry;
use Kurt\Modules\Core\Modules\Registry;
```

```php
    public function packageRegistered(): void
    {
        $this->app->singleton(UserResolver::class, fn ($app) => new ConfigUserResolver($app['config']));
        $this->app->singleton(ModuleRegistry::class, fn () => new Registry());
    }
```

- [ ] **Step 4: Add the `moduleManifest()` hook and registration to the base `PackageServiceProvider`**

In `src/Providers/PackageServiceProvider.php`, add imports:

```php
use Kurt\Modules\Core\Contracts\ModuleRegistry;
use Kurt\Modules\Core\Modules\ModuleManifest;
```

Replace the existing `packageBooted()` method with one that also registers the manifest, and add the two new methods:

```php
    public function packageBooted(): void
    {
        parent::packageBooted();

        $this->registerModuleManifest();

        $this->registerFilament();
    }

    /**
     * Register this module's manifest into the registry, if it declares one.
     * Runs in the boot phase, after CoreServiceProvider's register phase has
     * bound the {@see ModuleRegistry} singleton.
     */
    private function registerModuleManifest(): void
    {
        $manifest = $this->moduleManifest();

        if ($manifest instanceof ModuleManifest) {
            $this->app->make(ModuleRegistry::class)->register($manifest);
        }
    }

    /**
     * A module overrides this to declare itself into the registry. Returning
     * null (the default) means the package is not a managed module.
     */
    protected function moduleManifest(): ?ModuleManifest
    {
        return null;
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `"$PHP84" vendor/bin/pest tests/Feature/Modules/ModuleManifestRegistrationTest.php`
Expected: PASS (3 passed).

- [ ] **Step 6: Run the full suite + static analysis**

Run: `"$PHP84" vendor/bin/pest`
Expected: PASS (all green, including pre-existing core tests).

Run: `"$PHP84" vendor/bin/phpstan analyse --memory-limit=2G`
Expected: no errors.

- [ ] **Step 7: Commit**

```bash
git add src/Providers/CoreServiceProvider.php src/Providers/PackageServiceProvider.php tests/Feature/Modules/ModuleManifestRegistrationTest.php
git commit -m "feat: collect module manifests into the registry at boot"
```

---

## Done Criteria

- `ModuleManifest::make('x')->feature(...)->setting(...)` builds a full self-declaration with the getters in Task 1.
- `ModuleRegistry` is a container singleton; module providers self-register their manifest at boot via the `moduleManifest()` hook; a provider without one registers nothing.
- Full Pest suite + PHPStan green under PHP 8.4.

## Out of Scope (later milestones)

- M2: DB tables, `ModuleManager` resolution (scope -> global -> manifest default), `ScopeResolver`, caching, `Modules` facade - in the `laravel-modules-manager` package.
- M3: REST API + `EnsureModuleEnabled` middleware.
- Rolling out `moduleManifest()` overrides to the 10 family modules (mechanical follow-up once the mechanism ships).
