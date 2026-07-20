<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Concerns;

use Illuminate\Console\Command;

/**
 * Guards destructive or demo Artisan commands against accidental execution in
 * production. Consuming commands return FAILURE when the guard trips:
 *
 *     public function handle(): int
 *     {
 *         if ($this->abortIfProduction()) {
 *             return self::FAILURE;
 *         }
 *
 *         // ... seed demo data ...
 *
 *         return self::SUCCESS;
 *     }
 *
 * @mixin Command
 */
trait AbortsInProduction
{
    /**
     * Print an error and signal the caller to abort when running in production
     * without an explicit `--force` override.
     *
     * @return bool True when the command should abort (production + not forced).
     */
    protected function abortIfProduction(): bool
    {
        if (! $this->getLaravel()->isProduction()) {
            return false;
        }

        if ($this->isForced()) {
            return false;
        }

        $this->error('This command is disabled in production. Re-run with --force to override.');

        return true;
    }

    /**
     * Whether a truthy `--force` flag was supplied. Safe to call on commands
     * that do not declare the option (returns false rather than throwing).
     */
    protected function isForced(): bool
    {
        return $this->hasOption('force') && (bool) $this->option('force');
    }
}
