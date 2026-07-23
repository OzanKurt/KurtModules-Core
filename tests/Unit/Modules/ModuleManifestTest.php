<?php

declare(strict_types=1);

use Kurt\Modules\Core\Modules\ModuleManifest;

it('builds a manifest with defaults', function () {
    $m = ModuleManifest::make('blog');

    expect($m->slug())->toBe('blog')
        ->and($m->getName())->toBe('blog')            // name defaults to slug
        ->and($m->getVersion())->toBeNull()
        ->and($m->getDescription())->toBeNull()
        ->and($m->isEnabledByDefault())->toBeTrue()   // enabled unless opted out
        ->and($m->dependencies())->toBe([])
        ->and($m->features())->toBe([])
        ->and($m->settings())->toBe([]);
});

it('builds a fully populated manifest fluently', function () {
    $m = ModuleManifest::make('blog')
        ->name('Blog')
        ->version('1.0.0')
        ->description('Blog module')
        ->enabledByDefault(false)
        ->dependsOn('interactions', 'media-library')
        ->feature('comments', default: true)
        ->feature('reactions')
        ->setting('posts_per_page', default: 15, type: 'int');

    expect($m->getName())->toBe('Blog')
        ->and($m->getVersion())->toBe('1.0.0')
        ->and($m->getDescription())->toBe('Blog module')
        ->and($m->isEnabledByDefault())->toBeFalse()
        ->and($m->dependencies())->toBe(['interactions', 'media-library'])
        ->and($m->features())->toBe(['comments' => true, 'reactions' => false])
        ->and($m->hasFeature('comments'))->toBeTrue()
        ->and($m->featureDefault('comments'))->toBeTrue()
        ->and($m->featureDefault('reactions'))->toBeFalse()
        ->and($m->hasFeature('missing'))->toBeFalse()
        ->and($m->featureDefault('missing'))->toBeFalse()
        ->and($m->settings())->toBe(['posts_per_page' => ['default' => 15, 'type' => 'int']])
        ->and($m->hasSetting('posts_per_page'))->toBeTrue()
        ->and($m->settingDefault('posts_per_page'))->toBe(15)
        ->and($m->settingType('posts_per_page'))->toBe('int')
        ->and($m->hasSetting('missing'))->toBeFalse()
        ->and($m->settingDefault('missing'))->toBeNull()
        ->and($m->settingType('missing'))->toBeNull();
});
