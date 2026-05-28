# Changelog

All notable changes follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and [SemVer](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
