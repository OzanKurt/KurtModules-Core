<?php

declare(strict_types=1);

namespace Kurt\Modules\Core\Tests\Stubs;

use Illuminate\Console\Command;
use Kurt\Modules\Core\Concerns\AbortsInProduction;

final class StubDemoCommand extends Command
{
    use AbortsInProduction;

    protected $signature = 'stub:demo {--force : Bypass the production guard}';

    protected $description = 'Stub command exercising the production guard.';

    public function handle(): int
    {
        if ($this->abortIfProduction()) {
            return self::FAILURE;
        }

        $this->info('demo ran');

        return self::SUCCESS;
    }
}
