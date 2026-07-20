<?php

declare(strict_types=1);

use Kurt\Modules\Core\Tests\Stubs\StubDemoCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @return array{code:int, output:string}
 */
function runStubDemo(array $parameters = []): array
{
    $app = app();

    $command = new StubDemoCommand;
    $command->setLaravel($app);

    $output = new BufferedOutput;
    $code = $command->run(new ArrayInput($parameters), $output);

    return ['code' => $code, 'output' => $output->fetch()];
}

it('runs normally in a non-production environment', function () {
    $this->app['env'] = 'local';

    $result = runStubDemo();

    expect($this->app->isProduction())->toBeFalse();
    expect($result['code'])->toBe(0);
    expect($result['output'])->toContain('demo ran');
});

it('aborts in production without --force', function () {
    $this->app['env'] = 'production';

    $result = runStubDemo();

    expect($this->app->isProduction())->toBeTrue();
    expect($result['code'])->toBe(1);
    expect($result['output'])->toContain('disabled in production');
});

it('runs in production when --force is passed', function () {
    $this->app['env'] = 'production';

    $result = runStubDemo(['--force' => true]);

    expect($result['code'])->toBe(0);
    expect($result['output'])->toContain('demo ran');
});
