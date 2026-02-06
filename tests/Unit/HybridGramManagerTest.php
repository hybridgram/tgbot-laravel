<?php

declare(strict_types=1);

use HybridGram\Core\HybridGramBotManager;

beforeEach(function () {
    $manager = app(HybridGramBotManager::class);
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('botConfigs');
    $property->setAccessible(true);
    $property->setValue($manager, []);
});

test('can add new bot', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig = new HybridGram\Core\Config\BotConfig('token', 'main_bot', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes', new HybridGram\Core\Config\PollingModeConfig, null, 'bot');

    $result = $manager->withBot($botConfig);

    expect($result)->toBe($manager);
});

test('can use facade to add new bot', function () {
    $botConfig = new HybridGram\Core\Config\BotConfig('token', 'main_bot', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes', new HybridGram\Core\Config\PollingModeConfig, null, 'bot');
    $manager = app(HybridGramBotManager::class);
    $result = $manager->withBot($botConfig);

    expect($result)->toBeInstanceOf(HybridGramBotManager::class);
});

test('can add single bot config and retrieve it', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig = new HybridGram\Core\Config\BotConfig('token', 'main_bot', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes', new HybridGram\Core\Config\PollingModeConfig, null, 'bot');

    $manager->withBot($botConfig);
    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(1)
        ->and($retrievedConfigs[0])->toBe($botConfig)
        ->and($retrievedConfigs[0]->botName)->toBe('bot')
        ->and($retrievedConfigs[0]->token)->toBe('token')
        ->and($retrievedConfigs[0]->botId)->toBe('main_bot');
});

test('can add multiple bot configs and retrieve them', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig1 = new HybridGram\Core\Config\BotConfig('token1', 'main_bot1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'bot1');
    $botConfig2 = new HybridGram\Core\Config\BotConfig('token2', 'main_bot2', HybridGram\Core\UpdateMode\UpdateModeEnum::WEBHOOK, 'routes2', null, new HybridGram\Core\Config\WebhookModeConfig, 'bot2');
    $botConfig3 = new HybridGram\Core\Config\BotConfig('token3', 'main_bot3', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes3', new HybridGram\Core\Config\PollingModeConfig, null, 'bot3');

    $manager->withBot($botConfig1)
        ->withBot($botConfig2)
        ->withBot($botConfig3);

    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(3)
        ->and($retrievedConfigs[0])->toBe($botConfig1)
        ->and($retrievedConfigs[1])->toBe($botConfig2)
        ->and($retrievedConfigs[2])->toBe($botConfig3);
});

test('can add multiple bots at once using withBots method', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfigs = [
        new HybridGram\Core\Config\BotConfig('token1', 'main_bot1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'bot1'),
        new HybridGram\Core\Config\BotConfig('token2', 'main_bot2', HybridGram\Core\UpdateMode\UpdateModeEnum::WEBHOOK, 'routes2', null, new HybridGram\Core\Config\WebhookModeConfig, 'bot2'),
        new HybridGram\Core\Config\BotConfig('token3', 'main_bot3', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes3', new HybridGram\Core\Config\PollingModeConfig, null, 'bot3'),
    ];

    $manager->withBots($botConfigs);
    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(3)
        ->and($retrievedConfigs)->toContain($botConfigs[0])
        ->and($retrievedConfigs)->toContain($botConfigs[1])
        ->and($retrievedConfigs)->toContain($botConfigs[2]);
});

test('can mix single and multiple bot additions', function () {
    $manager = app(HybridGramBotManager::class);
    $singleBot = new HybridGram\Core\Config\BotConfig('singleToken', 'single', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'single', new HybridGram\Core\Config\PollingModeConfig, null, 'single');
    $multipleBots = [
        new HybridGram\Core\Config\BotConfig('token1', 'main_bot1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'bot1'),
        new HybridGram\Core\Config\BotConfig('token2', 'main_bot2', HybridGram\Core\UpdateMode\UpdateModeEnum::WEBHOOK, 'routes2', null, new HybridGram\Core\Config\WebhookModeConfig, 'bot2'),
    ];

    $manager->withBot($singleBot)
        ->withBots($multipleBots);

    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(3)
        ->and($retrievedConfigs)->toContain($singleBot)
        ->and($retrievedConfigs)->toContain($multipleBots[0])
        ->and($retrievedConfigs)->toContain($multipleBots[1]);
});

test('can use facade to add multiple bots', function () {
    $botConfig1 = new HybridGram\Core\Config\BotConfig('token1', 'main_bot1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'bot1');
    $botConfig2 = new HybridGram\Core\Config\BotConfig('token2', 'main_bot2', HybridGram\Core\UpdateMode\UpdateModeEnum::WEBHOOK, 'routes2', null, new HybridGram\Core\Config\WebhookModeConfig, 'bot2');

    $manager = app(HybridGramBotManager::class);
    $manager->withBot($botConfig1);
    $manager->withBot($botConfig2);

    $manager = app(HybridGramBotManager::class);
    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(2)
        ->and($retrievedConfigs)->toContain($botConfig1)
        ->and($retrievedConfigs)->toContain($botConfig2);
});

test('can handle empty array in withBots method', function () {
    $manager = app(HybridGramBotManager::class);
    $initialCount = count($manager->getBotConfigs());

    $manager->withBots([]);
    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount($initialCount);
});

test('can handle duplicate bot configs', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig = new HybridGram\Core\Config\BotConfig('token1', 'main_bot1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'bot1');
    $botConfigWebhook = new HybridGram\Core\Config\BotConfig('token1', 'main_bot1', HybridGram\Core\UpdateMode\UpdateModeEnum::WEBHOOK, 'routes1', null, new HybridGram\Core\Config\WebhookModeConfig, 'bot1');

    $manager->withBot($botConfig)
        ->withBot($botConfig)
        ->withBot($botConfigWebhook);

    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(1);
    expect($retrievedConfigs[0])->toBe($botConfig);
});

test('can handle bots with same properties but different instances', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig1 = new HybridGram\Core\Config\BotConfig('same_token', 'same_id', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes', new HybridGram\Core\Config\PollingModeConfig, null, 'SameName');
    $botConfig2 = new HybridGram\Core\Config\BotConfig('same_token', 'same_id', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes', new HybridGram\Core\Config\PollingModeConfig, null, 'SameName');

    $manager->withBot($botConfig1)
        ->withBot($botConfig2);

    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toHaveCount(1); // Should be 1 now, since botIds are the same
    expect($retrievedConfigs[0])->toBe($botConfig1);
});

test('getBotConfigs returns empty array when no bots added', function () {
    $manager = app(HybridGramBotManager::class);
    $retrievedConfigs = $manager->getBotConfigs();

    expect($retrievedConfigs)->toBeArray();
    expect($retrievedConfigs)->toHaveCount(0);
});

test('can chain multiple withBot calls', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig1 = new HybridGram\Core\Config\BotConfig('token1', 'id1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'Bot1');
    $botConfig2 = new HybridGram\Core\Config\BotConfig('token2', 'id2', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes2', new HybridGram\Core\Config\PollingModeConfig, null, 'Bot2');
    $botConfig3 = new HybridGram\Core\Config\BotConfig('token3', 'id3', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes3', new HybridGram\Core\Config\PollingModeConfig, null, 'Bot3');

    $result = $manager->withBot($botConfig1)
        ->withBot($botConfig2)
        ->withBot($botConfig3);

    expect($result)->toBe($manager); // Verify that the same object is returned
    expect($manager->getBotConfigs())->toHaveCount(3);
});

test('can chain withBots and withBot calls', function () {
    $manager = app(HybridGramBotManager::class);
    $initialBots = [
        new HybridGram\Core\Config\BotConfig('token1', 'id1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'InitialBot1'),
        new HybridGram\Core\Config\BotConfig('token2', 'id2', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes2', new HybridGram\Core\Config\PollingModeConfig, null, 'InitialBot2'),
    ];
    $additionalBot = new HybridGram\Core\Config\BotConfig('token3', 'id3', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes3', new HybridGram\Core\Config\PollingModeConfig, null, 'AdditionalBot');

    $result = $manager->withBots($initialBots)
        ->withBot($additionalBot);

    expect($result)->toBe($manager);
    expect($manager->getBotConfigs())->toHaveCount(3);
    expect($manager->getBotConfigs())->toContain($initialBots[0]);
    expect($manager->getBotConfigs())->toContain($initialBots[1]);
    expect($manager->getBotConfigs())->toContain($additionalBot);
});

test('manager is properly initialized with empty configs', function () {
    $manager = app(HybridGramBotManager::class);

    expect($manager)->toBeInstanceOf(HybridGramBotManager::class);
    expect($manager->getBotConfigs())->toBeArray();
    expect($manager->getBotConfigs())->toHaveCount(0);
});

test('can retrieve specific bot config by index', function () {
    $manager = app(HybridGramBotManager::class);
    $botConfig1 = new HybridGram\Core\Config\BotConfig('token1', 'id1', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes1', new HybridGram\Core\Config\PollingModeConfig, null, 'FirstBot');
    $botConfig2 = new HybridGram\Core\Config\BotConfig('token2', 'id2', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes2', new HybridGram\Core\Config\PollingModeConfig, null, 'SecondBot');
    $botConfig3 = new HybridGram\Core\Config\BotConfig('token3', 'id3', HybridGram\Core\UpdateMode\UpdateModeEnum::POLLING, 'routes3', new HybridGram\Core\Config\PollingModeConfig, null, 'ThirdBot');

    $manager->withBot($botConfig1)
        ->withBot($botConfig2)
        ->withBot($botConfig3);

    $configs = $manager->getBotConfigs();

    expect($configs[0])->toBe($botConfig1);
    expect($configs[1])->toBe($botConfig2);
    expect($configs[2])->toBe($botConfig3);

    // Verify order of addition
    expect($configs[0]->botName)->toBe('FirstBot');
    expect($configs[1]->botName)->toBe('SecondBot');
    expect($configs[2]->botName)->toBe('ThirdBot');
});
