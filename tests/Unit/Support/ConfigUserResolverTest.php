<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Kurt\Modules\Core\Support\ConfigUserResolver;
use Kurt\Modules\Core\Tests\Stubs\StubUser;

function makeConfig(array $overrides = []): Repository
{
    return new Repository(array_replace_recursive([
        'kurtmodules' => ['user_model' => null],
        'auth' => ['providers' => ['users' => ['model' => null]]],
    ], $overrides));
}

it('prefers kurtmodules.user_model over auth.providers.users.model', function () {
    $config = makeConfig([
        'kurtmodules' => ['user_model' => StubUser::class],
        'auth' => ['providers' => ['users' => ['model' => stdClass::class]]],
    ]);

    expect((new ConfigUserResolver($config))->modelClass())->toBe(StubUser::class);
});

it('falls back to auth.providers.users.model when kurtmodules.user_model is null', function () {
    $config = makeConfig([
        'auth' => ['providers' => ['users' => ['model' => StubUser::class]]],
    ]);

    expect((new ConfigUserResolver($config))->modelClass())->toBe(StubUser::class);
});

it('exposes primary key and table from the resolved model', function () {
    $config = makeConfig([
        'kurtmodules' => ['user_model' => StubUser::class],
    ]);

    $resolver = new ConfigUserResolver($config);

    expect($resolver->primaryKey())->toBe('id');
    expect($resolver->table())->toBe('users');
});

it('throws when neither config key is set', function () {
    $config = makeConfig();

    expect(fn () => (new ConfigUserResolver($config))->modelClass())
        ->toThrow(RuntimeException::class);
});
