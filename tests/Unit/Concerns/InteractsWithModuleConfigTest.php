<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Kurt\Modules\Core\Concerns\InteractsWithModuleConfig;

beforeEach(function () {
    $container = new Container;
    $container->instance('config', new Repository([
        'blog' => ['foo' => 'bar'],
    ]));
    Container::setInstance($container);
});

afterEach(function () {
    Container::setInstance(null);
});

it('reads a namespaced key', function () {
    $obj = new class
    {
        use InteractsWithModuleConfig;

        protected function module(): string
        {
            return 'blog';
        }

        public function probe(string $key, mixed $default = null): mixed
        {
            return $this->moduleConfig($key, $default);
        }
    };

    expect($obj->probe('foo'))->toBe('bar');
});

it('returns the default when the key is missing', function () {
    $obj = new class
    {
        use InteractsWithModuleConfig;

        protected function module(): string
        {
            return 'blog';
        }

        public function probe(string $key, mixed $default = null): mixed
        {
            return $this->moduleConfig($key, $default);
        }
    };

    expect($obj->probe('missing', 'default'))->toBe('default');
});
