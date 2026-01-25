<?php

declare(strict_types=1);

use HybridGram\Core\Config\BotSettings\LocalizedString;

it('creates empty localized string', function () {
    $localized = new LocalizedString;

    expect($localized->isEmpty())->toBeTrue();
    expect($localized->all())->toBeEmpty();
});

it('adds and retrieves default value', function () {
    $localized = new LocalizedString;
    $localized->add('Default value');

    expect($localized->isEmpty())->toBeFalse();
    expect($localized->get())->toBe('Default value');
    expect($localized->get(null))->toBe('Default value');
    expect($localized->has())->toBeTrue();
    expect($localized->has(null))->toBeTrue();
});

it('adds and retrieves language-specific values', function () {
    $localized = new LocalizedString;
    $localized
        ->add('English text', 'en')
        ->add('Текст на русском', 'ru')
        ->add('Deutscher Text', 'de');

    expect($localized->get('en'))->toBe('English text');
    expect($localized->get('ru'))->toBe('Текст на русском');
    expect($localized->get('de'))->toBe('Deutscher Text');
    expect($localized->get('fr'))->toBeNull();

    expect($localized->has('en'))->toBeTrue();
    expect($localized->has('ru'))->toBeTrue();
    expect($localized->has('fr'))->toBeFalse();
});

it('retrieves all values', function () {
    $localized = new LocalizedString;
    $localized
        ->add('Default', null)
        ->add('English', 'en')
        ->add('Russian', 'ru');

    $all = $localized->all();

    expect($all)->toHaveCount(3);
    expect($all[null])->toBe('Default');
    expect($all['en'])->toBe('English');
    expect($all['ru'])->toBe('Russian');
});

it('overwrites existing value for same language code', function () {
    $localized = new LocalizedString;
    $localized
        ->add('First value', 'en')
        ->add('Second value', 'en');

    expect($localized->get('en'))->toBe('Second value');
    expect($localized->all())->toHaveCount(1);
});

it('supports fluent chaining', function () {
    $localized = (new LocalizedString)
        ->add('Default')
        ->add('English', 'en')
        ->add('Russian', 'ru');

    expect($localized)->toBeInstanceOf(LocalizedString::class);
    expect($localized->all())->toHaveCount(3);
});
