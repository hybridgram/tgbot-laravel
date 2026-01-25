<?php

declare(strict_types=1);

namespace HybridGram\Core\Config\BotSettings;

use Phptg\BotApi\Type\BotCommand;
use Phptg\BotApi\Type\BotCommandScope;
use Phptg\BotApi\Type\ChatAdministratorRights;
use Phptg\BotApi\Type\MenuButton;

/**
 * Fluent builder for configuring Telegram bot settings with multilingual support.
 *
 * Usage example:
 * ```php
 * BotSettings::create()
 *     ->description('Default bot description')
 *     ->description('Описание бота', 'ru')
 *     ->description('Bot-Beschreibung', 'de')
 *     ->shortDescription('Short description')
 *     ->shortDescription('Короткое описание', 'ru')
 *     ->name('My Bot')
 *     ->name('Мой Бот', 'ru')
 *     ->menuButton(new MenuButtonWebApp('Open App', new WebAppInfo('https://example.com')))
 *     ->commands([
 *         new BotCommand('start', 'Start the bot'),
 *         new BotCommand('help', 'Get help'),
 *     ])
 *     ->commands([
 *         new BotCommand('start', 'Запустить бота'),
 *         new BotCommand('help', 'Получить помощь'),
 *     ], null, 'ru')
 *     ->defaultAdministratorRights(new ChatAdministratorRights(...))
 *     ->defaultAdministratorRightsForChannels(new ChatAdministratorRights(...));
 * ```
 */
final class BotSettings
{
    private LocalizedString $descriptions;

    private LocalizedString $shortDescriptions;

    private LocalizedString $names;

    private ?MenuButton $menuButton = null;

    private ?ChatAdministratorRights $defaultAdminRights = null;

    private ?ChatAdministratorRights $defaultAdminRightsForChannels = null;

    /**
     * @var array<int, array{commands: BotCommand[], scope: ?BotCommandScope, languageCode: ?string}>
     */
    private array $commands = [];

    public function __construct()
    {
        $this->descriptions = new LocalizedString;
        $this->shortDescriptions = new LocalizedString;
        $this->names = new LocalizedString;
    }

    /**
     * Create a new BotSettings instance.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set the bot description for a specific language.
     *
     * The description is shown in the chat with the bot if the chat is empty.
     * Maximum 512 characters.
     *
     * @param string $description The description text
     * @param string|null $languageCode ISO 639-1 language code. Null for default (all users without dedicated description).
     *
     * @see https://core.telegram.org/bots/api#setmydescription
     */
    public function description(string $description, ?string $languageCode = null): self
    {
        $this->descriptions->add($description, $languageCode);

        return $this;
    }

    /**
     * Set the bot short description for a specific language.
     *
     * The short description is shown on the bot's profile page and is sent together
     * with the link when users share the bot. Maximum 120 characters.
     *
     * @param string $shortDescription The short description text
     * @param string|null $languageCode ISO 639-1 language code. Null for default.
     *
     * @see https://core.telegram.org/bots/api#setmyshortdescription
     */
    public function shortDescription(string $shortDescription, ?string $languageCode = null): self
    {
        $this->shortDescriptions->add($shortDescription, $languageCode);

        return $this;
    }

    /**
     * Set the bot name for a specific language.
     *
     * Maximum 64 characters.
     *
     * @param string $name The bot name
     * @param string|null $languageCode ISO 639-1 language code. Null for default.
     *
     * @see https://core.telegram.org/bots/api#setmyname
     */
    public function name(string $name, ?string $languageCode = null): self
    {
        $this->names->add($name, $languageCode);

        return $this;
    }

    /**
     * Set the default bot menu button.
     *
     * This button appears in private chats with the bot.
     *
     * @param MenuButton $menuButton The menu button configuration
     *
     * @see https://core.telegram.org/bots/api#setchatmenubutton
     */
    public function menuButton(MenuButton $menuButton): self
    {
        $this->menuButton = $menuButton;

        return $this;
    }

    /**
     * Set the default administrator rights for groups and supergroups.
     *
     * These rights will be suggested to users when adding the bot as an administrator.
     *
     * @param ChatAdministratorRights $rights The administrator rights
     *
     * @see https://core.telegram.org/bots/api#setmydefaultadministratorrights
     */
    public function defaultAdministratorRights(ChatAdministratorRights $rights): self
    {
        $this->defaultAdminRights = $rights;

        return $this;
    }

    /**
     * Set the default administrator rights for channels.
     *
     * These rights will be suggested to users when adding the bot as an administrator to channels.
     *
     * @param ChatAdministratorRights $rights The administrator rights
     *
     * @see https://core.telegram.org/bots/api#setmydefaultadministratorrights
     */
    public function defaultAdministratorRightsForChannels(ChatAdministratorRights $rights): self
    {
        $this->defaultAdminRightsForChannels = $rights;

        return $this;
    }

    /**
     * Set bot commands for a specific scope and language.
     *
     * @param BotCommand[] $commands List of bot commands
     * @param BotCommandScope|null $scope Scope of users for which the commands are relevant
     * @param string|null $languageCode ISO 639-1 language code. Null for all users.
     *
     * @see https://core.telegram.org/bots/api#setmycommands
     */
    public function commands(array $commands, ?BotCommandScope $scope = null, ?string $languageCode = null): self
    {
        $this->commands[] = [
            'commands' => $commands,
            'scope' => $scope,
            'languageCode' => $languageCode,
        ];

        return $this;
    }

    /**
     * Get all description localizations.
     */
    public function getDescriptions(): LocalizedString
    {
        return $this->descriptions;
    }

    /**
     * Get all short description localizations.
     */
    public function getShortDescriptions(): LocalizedString
    {
        return $this->shortDescriptions;
    }

    /**
     * Get all name localizations.
     */
    public function getNames(): LocalizedString
    {
        return $this->names;
    }

    /**
     * Get the menu button configuration.
     */
    public function getMenuButton(): ?MenuButton
    {
        return $this->menuButton;
    }

    /**
     * Get the default administrator rights for groups/supergroups.
     */
    public function getDefaultAdministratorRights(): ?ChatAdministratorRights
    {
        return $this->defaultAdminRights;
    }

    /**
     * Get the default administrator rights for channels.
     */
    public function getDefaultAdministratorRightsForChannels(): ?ChatAdministratorRights
    {
        return $this->defaultAdminRightsForChannels;
    }

    /**
     * Get all command configurations.
     *
     * @return array<int, array{commands: BotCommand[], scope: ?BotCommandScope, languageCode: ?string}>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Check if any settings are configured.
     */
    public function isEmpty(): bool
    {
        return $this->descriptions->isEmpty()
            && $this->shortDescriptions->isEmpty()
            && $this->names->isEmpty()
            && $this->menuButton === null
            && $this->defaultAdminRights === null
            && $this->defaultAdminRightsForChannels === null
            && empty($this->commands);
    }
}
