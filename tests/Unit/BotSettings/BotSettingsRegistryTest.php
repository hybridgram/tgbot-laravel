<?php

declare(strict_types=1);

use HybridGram\Core\Config\BotSettings\BotSettings;
use HybridGram\Core\Config\BotSettings\BotSettingsRegistry;

beforeEach(function () {
    BotSettingsRegistry::clear();
});

afterEach(function () {
    BotSettingsRegistry::clear();
});

it('registers and retrieves settings for bot', function () {
    $settings = BotSettings::create()
        ->description('Test description');

    BotSettingsRegistry::forBot('main', fn () => $settings);

    $retrieved = BotSettingsRegistry::get('main');

    expect($retrieved)->toBeInstanceOf(BotSettings::class);
    expect($retrieved->getDescriptions()->get())->toBe('Test description');
});

it('returns null for unregistered bot', function () {
    expect(BotSettingsRegistry::get('nonexistent'))->toBeNull();
});

it('checks if bot has registered settings', function () {
    BotSettingsRegistry::forBot('main', fn () => BotSettings::create());

    expect(BotSettingsRegistry::has('main'))->toBeTrue();
    expect(BotSettingsRegistry::has('other'))->toBeFalse();
});

it('lists all registered bots', function () {
    BotSettingsRegistry::forBot('main', fn () => BotSettings::create());
    BotSettingsRegistry::forBot('secondary', fn () => BotSettings::create());
    BotSettingsRegistry::forBot('third', fn () => BotSettings::create());

    $bots = BotSettingsRegistry::registeredBots();

    expect($bots)->toContain('main');
    expect($bots)->toContain('secondary');
    expect($bots)->toContain('third');
    expect($bots)->toHaveCount(3);
});

it('clears all settings', function () {
    BotSettingsRegistry::forBot('main', fn () => BotSettings::create());
    BotSettingsRegistry::forBot('secondary', fn () => BotSettings::create());

    BotSettingsRegistry::clear();

    expect(BotSettingsRegistry::has('main'))->toBeFalse();
    expect(BotSettingsRegistry::has('secondary'))->toBeFalse();
    expect(BotSettingsRegistry::registeredBots())->toBeEmpty();
});

it('clears settings for specific bot', function () {
    BotSettingsRegistry::forBot('main', fn () => BotSettings::create());
    BotSettingsRegistry::forBot('secondary', fn () => BotSettings::create());

    BotSettingsRegistry::clearBot('main');

    expect(BotSettingsRegistry::has('main'))->toBeFalse();
    expect(BotSettingsRegistry::has('secondary'))->toBeTrue();
});

it('overwrites settings when registered twice', function () {
    BotSettingsRegistry::forBot('main', fn () => BotSettings::create()->description('First'));
    BotSettingsRegistry::forBot('main', fn () => BotSettings::create()->description('Second'));

    $settings = BotSettingsRegistry::get('main');

    expect($settings->getDescriptions()->get())->toBe('Second');
});

it('executes callback lazily on get', function () {
    $callCount = 0;

    BotSettingsRegistry::forBot('main', function () use (&$callCount) {
        $callCount++;

        return BotSettings::create()->description('Test');
    });

    expect($callCount)->toBe(0);

    BotSettingsRegistry::get('main');
    expect($callCount)->toBe(1);

    BotSettingsRegistry::get('main');
    expect($callCount)->toBe(2);
});
