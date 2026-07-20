<?php

declare(strict_types=1);

use Kurt\Modules\Core\Http\HttpMode;

it('defaults to headless when no config is set', function () {
    expect(HttpMode::forModule('demo'))->toBe(HttpMode::Headless);
});

it('resolves each configured mode', function (string $value, HttpMode $expected) {
    config()->set('demo.http.mode', $value);

    expect(HttpMode::forModule('demo'))->toBe($expected);
})->with([
    ['headless', HttpMode::Headless],
    ['api', HttpMode::Api],
    ['ui', HttpMode::Ui],
]);

it('falls back to headless for an unknown mode', function () {
    config()->set('demo.http.mode', 'nonsense');

    expect(HttpMode::forModule('demo'))->toBe(HttpMode::Headless);
});

it('falls back to headless for a non-string mode', function () {
    config()->set('demo.http.mode', ['api']);

    expect(HttpMode::forModule('demo'))->toBe(HttpMode::Headless);
});

it('reports apiEnabled for api and ui only', function () {
    expect(HttpMode::Headless->apiEnabled())->toBeFalse()
        ->and(HttpMode::Api->apiEnabled())->toBeTrue()
        ->and(HttpMode::Ui->apiEnabled())->toBeTrue();
});

it('matches modes via is()', function () {
    expect(HttpMode::Api->is(HttpMode::Api, HttpMode::Ui))->toBeTrue()
        ->and(HttpMode::Headless->is(HttpMode::Api, HttpMode::Ui))->toBeFalse();
});
