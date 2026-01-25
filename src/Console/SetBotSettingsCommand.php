<?php

declare(strict_types=1);

namespace HybridGram\Console;

use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\Config\BotSettings\BotSettingsApplier;
use HybridGram\Core\Config\BotSettings\BotSettingsRegistry;
use HybridGram\Telegram\Sender\OutgoingDispatcherInterface;
use HybridGram\Telegram\TelegramBotApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

/**
 * Command to apply bot settings (description, name, commands, etc.) to Telegram.
 *
 * Configure settings in your service provider using BotSettingsRegistry::forBot().
 */
final class SetBotSettingsCommand extends Command
{
    protected $signature = 'hybridgram:settings:apply 
        {--bot=main : Bot ID to apply settings for}
        {--all : Apply settings for all registered bots}
        {--dry-run : Show what would be applied without making API calls}';

    protected $description = 'Apply bot settings (description, name, commands, etc.) to Telegram';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->applyAll();
        }

        return $this->applyForBot($this->option('bot'));
    }

    private function applyForBot(string $botId): int
    {
        $config = BotConfig::getBotConfig($botId);

        if ($config === null) {
            $this->error("Bot config not found for bot: {$botId}");

            return self::FAILURE;
        }

        $settings = BotSettingsRegistry::get($botId);

        if ($settings === null) {
            $this->warn("No settings registered for bot: {$botId}");
            $this->line('Register settings in your service provider:');
            $this->line('');
            $this->line("    BotSettingsRegistry::forBot('{$botId}', function () {");
            $this->line('        return BotSettings::create()');
            $this->line("            ->description('Your bot description')");
            $this->line("            ->description('Описание вашего бота', 'ru');");
            $this->line('    });');

            return self::FAILURE;
        }

        if ($settings->isEmpty()) {
            $this->warn("Settings for bot '{$botId}' are empty.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->showDryRun($botId, $settings);

            return self::SUCCESS;
        }

        $this->info("Applying settings for bot: {$botId}");

        $dispatcher = App::make(OutgoingDispatcherInterface::class);
        $api = (new TelegramBotApi($config->token, 'https://api.telegram.org', null, $dispatcher))
            ->withBotId($botId);

        $applier = new BotSettingsApplier($api);
        $results = $applier->apply($settings);

        $this->displayResults($results);

        if ($applier->allSuccessful()) {
            $this->newLine();
            $this->info("All settings applied successfully for bot: {$botId}");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error("Some settings failed to apply for bot: {$botId}");

        return self::FAILURE;
    }

    private function applyAll(): int
    {
        $bots = BotSettingsRegistry::registeredBots();

        if (empty($bots)) {
            $this->warn('No bots have registered settings.');

            return self::FAILURE;
        }

        $this->info('Applying settings for all registered bots: '.implode(', ', $bots));
        $this->newLine();

        $hasFailures = false;

        foreach ($bots as $botId) {
            $this->line("--- Bot: {$botId} ---");
            $result = $this->applyForBot($botId);

            if ($result !== self::SUCCESS) {
                $hasFailures = true;
            }

            $this->newLine();
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    private function showDryRun(string $botId, \HybridGram\Core\Config\BotSettings\BotSettings $settings): void
    {
        $this->info("[DRY RUN] Would apply the following settings for bot: {$botId}");
        $this->newLine();

        $descriptions = $settings->getDescriptions();
        if (! $descriptions->isEmpty()) {
            $this->line('<comment>Descriptions:</comment>');
            foreach ($descriptions->all() as $langCode => $desc) {
                $lang = $langCode ?: 'default';
                $this->line("  [{$lang}] {$desc}");
            }
        }

        $shortDescriptions = $settings->getShortDescriptions();
        if (! $shortDescriptions->isEmpty()) {
            $this->line('<comment>Short Descriptions:</comment>');
            foreach ($shortDescriptions->all() as $langCode => $desc) {
                $lang = $langCode ?: 'default';
                $this->line("  [{$lang}] {$desc}");
            }
        }

        $names = $settings->getNames();
        if (! $names->isEmpty()) {
            $this->line('<comment>Names:</comment>');
            foreach ($names->all() as $langCode => $name) {
                $lang = $langCode ?: 'default';
                $this->line("  [{$lang}] {$name}");
            }
        }

        if ($settings->getMenuButton() !== null) {
            $this->line('<comment>Menu Button:</comment> '.get_class($settings->getMenuButton()));
        }

        if ($settings->getDefaultAdministratorRights() !== null) {
            $this->line('<comment>Admin Rights (Groups):</comment> configured');
        }

        if ($settings->getDefaultAdministratorRightsForChannels() !== null) {
            $this->line('<comment>Admin Rights (Channels):</comment> configured');
        }

        $commands = $settings->getCommands();
        if (! empty($commands)) {
            $this->line('<comment>Commands:</comment>');
            foreach ($commands as $index => $cmdConfig) {
                $scopeLabel = $cmdConfig['scope'] !== null
                    ? class_basename($cmdConfig['scope']::class)
                    : 'default';
                $lang = $cmdConfig['languageCode'] ?: 'all';
                $count = count($cmdConfig['commands']);
                $this->line("  [{$index}] scope: {$scopeLabel}, lang: {$lang}, commands: {$count}");
            }
        }
    }

    /**
     * @param array<string, array{success: bool, error: ?string}> $results
     */
    private function displayResults(array $results): void
    {
        foreach ($results as $key => $result) {
            if ($result['success']) {
                $this->line("  <info>[OK]</info> {$key}");
            } else {
                $this->line("  <error>[FAIL]</error> {$key}: {$result['error']}");
            }
        }
    }
}
