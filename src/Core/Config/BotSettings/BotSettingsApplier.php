<?php

declare(strict_types=1);

namespace HybridGram\Core\Config\BotSettings;

use HybridGram\Telegram\TelegramBotApi;
use Phptg\BotApi\FailResult;

/**
 * Applies BotSettings configuration to a Telegram bot via API.
 */
final class BotSettingsApplier
{
    /**
     * Results of the last apply operation.
     *
     * @var array<string, array{success: bool, error: ?string}>
     */
    private array $results = [];

    public function __construct(
        private readonly TelegramBotApi $api,
    ) {}

    /**
     * Apply all settings from BotSettings to the Telegram bot.
     *
     * @return array<string, array{success: bool, error: ?string}> Results of each operation
     */
    public function apply(BotSettings $settings): array
    {
        $this->results = [];

        $this->applyDescriptions($settings);
        $this->applyShortDescriptions($settings);
        $this->applyNames($settings);
        $this->applyMenuButton($settings);
        $this->applyDefaultAdministratorRights($settings);
        $this->applyCommands($settings);

        return $this->results;
    }

    /**
     * Apply only descriptions.
     */
    public function applyDescriptions(BotSettings $settings): void
    {
        foreach ($settings->getDescriptions()->all() as $languageCode => $description) {
            $key = $this->makeKey('description', $languageCode);
            $result = $this->api->setMyDescription($description, $languageCode);

            $this->recordResult($key, $result);
        }
    }

    /**
     * Apply only short descriptions.
     */
    public function applyShortDescriptions(BotSettings $settings): void
    {
        foreach ($settings->getShortDescriptions()->all() as $languageCode => $shortDescription) {
            $key = $this->makeKey('short_description', $languageCode);
            $result = $this->api->setMyShortDescription($shortDescription, $languageCode);

            $this->recordResult($key, $result);
        }
    }

    /**
     * Apply only names.
     */
    public function applyNames(BotSettings $settings): void
    {
        foreach ($settings->getNames()->all() as $languageCode => $name) {
            $key = $this->makeKey('name', $languageCode);
            $result = $this->api->setMyName($name, $languageCode);

            $this->recordResult($key, $result);
        }
    }

    /**
     * Apply only the menu button.
     */
    public function applyMenuButton(BotSettings $settings): void
    {
        $menuButton = $settings->getMenuButton();
        if ($menuButton === null) {
            return;
        }

        $result = $this->api->setChatMenuButton(menuButton: $menuButton);
        $this->recordResult('menu_button', $result);
    }

    /**
     * Apply default administrator rights.
     */
    public function applyDefaultAdministratorRights(BotSettings $settings): void
    {
        $groupRights = $settings->getDefaultAdministratorRights();
        if ($groupRights !== null) {
            $result = $this->api->setMyDefaultAdministratorRights($groupRights, forChannels: false);
            $this->recordResult('admin_rights_groups', $result);
        }

        $channelRights = $settings->getDefaultAdministratorRightsForChannels();
        if ($channelRights !== null) {
            $result = $this->api->setMyDefaultAdministratorRights($channelRights, forChannels: true);
            $this->recordResult('admin_rights_channels', $result);
        }
    }

    /**
     * Apply commands.
     */
    public function applyCommands(BotSettings $settings): void
    {
        foreach ($settings->getCommands() as $index => $commandConfig) {
            $scopeLabel = $commandConfig['scope'] !== null
                ? $commandConfig['scope']::class
                : 'default';
            $key = $this->makeKey("commands[{$index}]:{$scopeLabel}", $commandConfig['languageCode']);

            $result = $this->api->setMyCommands(
                $commandConfig['commands'],
                $commandConfig['scope'],
                $commandConfig['languageCode'],
            );

            $this->recordResult($key, $result);
        }
    }

    /**
     * Get results of the last apply operation.
     *
     * @return array<string, array{success: bool, error: ?string}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Check if all operations in the last apply were successful.
     */
    public function allSuccessful(): bool
    {
        foreach ($this->results as $result) {
            if (! $result['success']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get only failed results.
     *
     * @return array<string, array{success: bool, error: ?string}>
     */
    public function getFailures(): array
    {
        return array_filter($this->results, fn (array $result) => ! $result['success']);
    }

    private function makeKey(string $type, ?string $languageCode): string
    {
        if ($languageCode === null || $languageCode === '') {
            return $type;
        }

        return "{$type}:{$languageCode}";
    }

    private function recordResult(string $key, FailResult|true $result): void
    {
        if ($result instanceof FailResult) {
            $this->results[$key] = [
                'success' => false,
                'error' => $result->description ?? $result->response->body ?? 'Unknown error',
            ];
        } else {
            $this->results[$key] = [
                'success' => true,
                'error' => null,
            ];
        }
    }
}
