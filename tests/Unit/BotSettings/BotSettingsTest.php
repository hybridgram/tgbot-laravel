<?php

declare(strict_types=1);

use HybridGram\Core\Config\BotSettings\BotSettings;
use Phptg\BotApi\Type\BotCommand;
use Phptg\BotApi\Type\BotCommandScope\BotCommandScopeAllPrivateChats;
use Phptg\BotApi\Type\ChatAdministratorRights;
use Phptg\BotApi\Type\MenuButton\MenuButtonCommands;
use Phptg\BotApi\Type\MenuButton\MenuButtonDefault;

it('creates empty settings', function () {
    $settings = BotSettings::create();

    expect($settings->isEmpty())->toBeTrue();
    expect($settings->getDescriptions()->isEmpty())->toBeTrue();
    expect($settings->getShortDescriptions()->isEmpty())->toBeTrue();
    expect($settings->getNames()->isEmpty())->toBeTrue();
    expect($settings->getMenuButton())->toBeNull();
    expect($settings->getDefaultAdministratorRights())->toBeNull();
    expect($settings->getDefaultAdministratorRightsForChannels())->toBeNull();
    expect($settings->getCommands())->toBeEmpty();
});

it('sets description for default language', function () {
    $settings = BotSettings::create()
        ->description('Default description');

    expect($settings->isEmpty())->toBeFalse();
    expect($settings->getDescriptions()->get())->toBe('Default description');
    expect($settings->getDescriptions()->get(null))->toBe('Default description');
});

it('sets description for multiple languages', function () {
    $settings = BotSettings::create()
        ->description('Default description')
        ->description('Описание бота', 'ru')
        ->description('Bot-Beschreibung', 'de');

    expect($settings->getDescriptions()->get())->toBe('Default description');
    expect($settings->getDescriptions()->get('ru'))->toBe('Описание бота');
    expect($settings->getDescriptions()->get('de'))->toBe('Bot-Beschreibung');
    expect($settings->getDescriptions()->get('fr'))->toBeNull();
});

it('sets short description for multiple languages', function () {
    $settings = BotSettings::create()
        ->shortDescription('Short desc')
        ->shortDescription('Короткое описание', 'ru');

    expect($settings->getShortDescriptions()->get())->toBe('Short desc');
    expect($settings->getShortDescriptions()->get('ru'))->toBe('Короткое описание');
});

it('sets name for multiple languages', function () {
    $settings = BotSettings::create()
        ->name('My Bot')
        ->name('Мой Бот', 'ru')
        ->name('Mein Bot', 'de');

    expect($settings->getNames()->get())->toBe('My Bot');
    expect($settings->getNames()->get('ru'))->toBe('Мой Бот');
    expect($settings->getNames()->get('de'))->toBe('Mein Bot');
});

it('sets menu button', function () {
    $menuButton = new MenuButtonCommands;
    $settings = BotSettings::create()
        ->menuButton($menuButton);

    expect($settings->getMenuButton())->toBe($menuButton);
});

it('sets default administrator rights for groups', function () {
    $rights = new ChatAdministratorRights(
        isAnonymous: false,
        canManageChat: true,
        canDeleteMessages: true,
        canManageVideoChats: false,
        canRestrictMembers: true,
        canPromoteMembers: false,
        canChangeInfo: true,
        canInviteUsers: true,
        canPostStories: false,
        canEditStories: false,
        canDeleteStories: false,
    );

    $settings = BotSettings::create()
        ->defaultAdministratorRights($rights);

    expect($settings->getDefaultAdministratorRights())->toBe($rights);
    expect($settings->getDefaultAdministratorRightsForChannels())->toBeNull();
});

it('sets default administrator rights for channels', function () {
    $rights = new ChatAdministratorRights(
        isAnonymous: false,
        canManageChat: true,
        canDeleteMessages: true,
        canManageVideoChats: false,
        canRestrictMembers: false,
        canPromoteMembers: false,
        canChangeInfo: true,
        canInviteUsers: true,
        canPostStories: true,
        canEditStories: true,
        canDeleteStories: true,
    );

    $settings = BotSettings::create()
        ->defaultAdministratorRightsForChannels($rights);

    expect($settings->getDefaultAdministratorRights())->toBeNull();
    expect($settings->getDefaultAdministratorRightsForChannels())->toBe($rights);
});

it('sets commands with scope and language', function () {
    $commands = [
        new BotCommand('start', 'Start the bot'),
        new BotCommand('help', 'Get help'),
    ];
    $commandsRu = [
        new BotCommand('start', 'Запустить бота'),
        new BotCommand('help', 'Получить помощь'),
    ];
    $scope = new BotCommandScopeAllPrivateChats;

    $settings = BotSettings::create()
        ->commands($commands)
        ->commands($commandsRu, null, 'ru')
        ->commands($commands, $scope);

    $allCommands = $settings->getCommands();
    expect($allCommands)->toHaveCount(3);

    expect($allCommands[0]['commands'])->toBe($commands);
    expect($allCommands[0]['scope'])->toBeNull();
    expect($allCommands[0]['languageCode'])->toBeNull();

    expect($allCommands[1]['commands'])->toBe($commandsRu);
    expect($allCommands[1]['scope'])->toBeNull();
    expect($allCommands[1]['languageCode'])->toBe('ru');

    expect($allCommands[2]['commands'])->toBe($commands);
    expect($allCommands[2]['scope'])->toBe($scope);
    expect($allCommands[2]['languageCode'])->toBeNull();
});

it('supports fluent chaining for all settings', function () {
    $menuButton = new MenuButtonDefault;
    $groupRights = new ChatAdministratorRights(
        isAnonymous: false,
        canManageChat: true,
        canDeleteMessages: true,
        canManageVideoChats: false,
        canRestrictMembers: true,
        canPromoteMembers: false,
        canChangeInfo: true,
        canInviteUsers: true,
        canPostStories: false,
        canEditStories: false,
        canDeleteStories: false,
    );
    $channelRights = new ChatAdministratorRights(
        isAnonymous: false,
        canManageChat: true,
        canDeleteMessages: true,
        canManageVideoChats: false,
        canRestrictMembers: false,
        canPromoteMembers: false,
        canChangeInfo: true,
        canInviteUsers: true,
        canPostStories: true,
        canEditStories: true,
        canDeleteStories: true,
    );
    $commands = [new BotCommand('start', 'Start')];

    $settings = BotSettings::create()
        ->description('Default description')
        ->description('Описание', 'ru')
        ->shortDescription('Short')
        ->shortDescription('Короткое', 'ru')
        ->name('Bot')
        ->name('Бот', 'ru')
        ->menuButton($menuButton)
        ->defaultAdministratorRights($groupRights)
        ->defaultAdministratorRightsForChannels($channelRights)
        ->commands($commands)
        ->commands($commands, null, 'ru');

    expect($settings->isEmpty())->toBeFalse();
    expect($settings->getDescriptions()->all())->toHaveCount(2);
    expect($settings->getShortDescriptions()->all())->toHaveCount(2);
    expect($settings->getNames()->all())->toHaveCount(2);
    expect($settings->getMenuButton())->toBe($menuButton);
    expect($settings->getDefaultAdministratorRights())->toBe($groupRights);
    expect($settings->getDefaultAdministratorRightsForChannels())->toBe($channelRights);
    expect($settings->getCommands())->toHaveCount(2);
});
