<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Kurt\Modules\Core\Contracts\UserResolver;
use Kurt\Modules\Core\Support\ConfigUserResolver;
use Kurt\Modules\Core\Tests\Stubs\StubUser;

it('binds UserResolver to ConfigUserResolver', function () {
    expect(app(UserResolver::class))->toBeInstanceOf(ConfigUserResolver::class);
});

it('publishes config under kurtmodules key', function () {
    expect(config('kurtmodules.date_format'))->toBe('Y-m-d H:i:s');
});

it('creates the shared users table via defineDatabaseMigrations', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
});

it('resolves the user model when configured', function () {
    config()->set('kurtmodules.user_model', StubUser::class);

    $resolver = app(UserResolver::class);

    expect($resolver->modelClass())->toBe(StubUser::class);
    expect($resolver->primaryKey())->toBe('id');
    expect($resolver->table())->toBe('users');
});
