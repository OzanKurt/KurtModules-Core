<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Contracts\UserResolver;
use Kurt\Modules\Core\Support\ConfigUserResolver;
use Kurt\Modules\Core\Tests\Stubs\StubPost;
use Kurt\Modules\Core\Tests\Stubs\StubUser;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('resolves the bound UserResolver via the trait', function () {
    expect((new StubPost)->resolver())->toBeInstanceOf(ConfigUserResolver::class);
});

it('builds a BelongsTo targeting the configured user model with the right keys', function () {
    $relation = (new StubPost)->user();

    expect($relation)->toBeInstanceOf(BelongsTo::class)
        ->and($relation->getRelated())->toBeInstanceOf(StubUser::class)
        ->and($relation->getForeignKeyName())->toBe('user_id')
        ->and($relation->getOwnerKeyName())->toBe('id');
});

it('honours a custom foreign key', function () {
    $relation = (new StubPost)->user('author_id');

    expect($relation->getForeignKeyName())->toBe('author_id')
        ->and($relation->getOwnerKeyName())->toBe('id');
});

it('newQuery returns an Eloquent Builder for the user model', function () {
    $resolver = app(UserResolver::class);

    $query = $resolver->newQuery();

    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->getModel())->toBeInstanceOf(StubUser::class);
});
