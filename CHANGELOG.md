# Changelog

All notable changes follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and [SemVer](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **API kit**: shared config-convention-driven REST foundation every module inherits.
  - `Http\HttpMode` enum (`headless`/`api`/`ui`) with safe `forModule()` resolution.
  - `Http\ApiRouteGroup` — builds `Route::group()` attributes from a module's `http` config.
  - `Http\Controllers\ApiController` — abstract base with a consistent success/error envelope.
  - `Http\Concerns\HandlesApiQuery` — allow-list-driven sorting, filtering and pagination.
  - `Support\ApiRateLimiter` — registers the per-module `{slug}-api` throttle.
  - `PackageServiceProvider::registerModuleApi()` — no-op in headless mode; wires the limiter and routes otherwise.

## [2.0.0] - 2026-05-28

### Added
- `PackageServiceProvider` abstract base wrapping `spatie/laravel-package-tools` with Filament major-version dispatch.
- `UserResolver` contract + `ConfigUserResolver` default implementation.
- `ResolvesUser` and `InteractsWithModuleConfig` traits.
- `FilamentVersion` detector.
- `Approval`, `MediaKind`, `Visibility` enums.
- `PackageTestCase` Testbench-backed test base.
- GitHub Actions matrix CI (PHP 8.4 × Laravel 12/13).

### Removed
- Everything from v0.x — see UPGRADE-2.0.md.
