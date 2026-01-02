<?php

declare(strict_types=1);

namespace HybridGram\Console;

use Illuminate\Console\Command;
use HybridGram\Core\HybridGramBotManager;
use Symfony\Component\Process\Process;

final class StartPollingCommand extends Command
{
    protected $signature = 'hybridgram:polling
                            {botId? : Bot id from config (default: all polling bots)}
                            {--log-updates : Print a one-line summary for every received update}
                            {--full : Print full update payload as pretty JSON (implies --log-updates)}
                            {--hot-reload : Dev-only. Auto-restart polling on code changes (no manual restart)}
                            {--watch= : Comma-separated paths to watch for changes (relative to base_path). Default: app,routes,config,src}
                            {--watch-interval=1 : Seconds between file scans in --hot-reload mode}';

    protected $description = 'Polling telegram updates';

    public function handle(HybridGramBotManager $botManager): void
    {
        if ((bool) $this->option('hot-reload')) {
            $this->runWithHotReload();
            return;
        }

        $this->info('Starting polling...');
        $botManager->setCommand($this);
        $botManager->run($this->argument('botId'));
    }

    private function runWithHotReload(): void
    {
        $artisan = base_path('artisan');
        if (!is_file($artisan)) {
            $this->error("Cannot use --hot-reload: artisan not found at '{$artisan}'.");
            return;
        }

        $watchPaths = $this->resolveWatchPaths();
        $intervalSeconds = $this->resolveWatchIntervalSeconds();

        $this->info('Starting polling in hot-reload mode...');
        $this->line('<fg=gray>Watching:</fg=gray> ' . implode(', ', $watchPaths));
        $this->line('<fg=gray>Scan interval:</fg=gray> ' . rtrim(rtrim((string) $intervalSeconds, '0'), '.') . 's');

        $stopRequested = false;
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () use (&$stopRequested) {
                $stopRequested = true;
            });
            pcntl_signal(SIGTERM, function () use (&$stopRequested) {
                $stopRequested = true;
            });
        }

        $lastMTime = $this->scanLatestMTime($watchPaths);
        $restartCount = 0;
        $lastRestartAt = 0.0;

        while (true) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if ($stopRequested) {
                $this->info('Stopping...');
                return;
            }

            $process = $this->startChildPollingProcess(PHP_BINARY, $artisan);
            $restartCount++;
            $lastRestartAt = microtime(true);

            while ($process->isRunning()) {
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }

                if ($stopRequested) {
                    $this->stopChildProcess($process);
                    $this->info('Stopped.');
                    return;
                }

                usleep((int) max(50_000, $intervalSeconds * 1_000_000));

                $currentMTime = $this->scanLatestMTime($watchPaths);
                if ($currentMTime > $lastMTime) {
                    $lastMTime = $currentMTime;
                    $this->warn('Change detected. Restarting polling...');
                    $this->stopChildProcess($process);
                    break; // start a fresh child
                }
            }

            // If child exited on its own, restart it (but avoid tight loops)
            if (!$stopRequested && !$process->isRunning()) {
                $exitCode = $process->getExitCode();
                $timeSinceRestart = microtime(true) - $lastRestartAt;
                if ($timeSinceRestart < 0.5) {
                    usleep(500_000);
                }

                $this->line(sprintf(
                    '<fg=gray>[hot-reload]</fg=gray> child exited (code=%s), restarting...',
                    $exitCode === null ? 'null' : (string) $exitCode
                ));
            }
        }
    }

    /**
     * @return string[] absolute paths
     */
    private function resolveWatchPaths(): array
    {
        $raw = (string) ($this->option('watch') ?? '');
        $items = array_values(array_filter(array_map('trim', $raw !== '' ? explode(',', $raw) : [])));

        if ($items === []) {
            $items = ['app', 'routes', 'config', 'src'];
        }

        $paths = [];
        foreach ($items as $item) {
            $path = $this->isAbsolutePath($item) ? $item : base_path($item);
            if (file_exists($path)) {
                $paths[] = $path;
            }
        }

        // Fallback: at least watch the base path itself (without vendor)
        if ($paths === []) {
            $paths[] = base_path();
        }

        return array_values(array_unique($paths));
    }

    private function resolveWatchIntervalSeconds(): float
    {
        $raw = (string) ($this->option('watch-interval') ?? '1');
        $value = (float) $raw;
        if (!is_finite($value) || $value <= 0) {
            return 1.0;
        }
        return min(max($value, 0.1), 10.0);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // Unix absolute
        if ($path[0] === DIRECTORY_SEPARATOR) {
            return true;
        }

        // Windows absolute (e.g. C:\...)
        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    /**
     * Returns latest mtime across watched paths, ignoring vendor/ and .git/.
     */
    private function scanLatestMTime(array $paths): int
    {
        $latest = 0;
        $extPattern = '/\.(php|env|json|ya?ml)$/i';

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (is_file($path)) {
                $name = basename($path);
                if (preg_match($extPattern, $name)) {
                    $mtime = @filemtime($path) ?: 0;
                    $latest = max($latest, (int) $mtime);
                }
                continue;
            }

            try {
                $dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
                $it = new \RecursiveIteratorIterator($dir);
            } catch (\Throwable) {
                continue;
            }

            /** @var \SplFileInfo $file */
            foreach ($it as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $pathname = $file->getPathname();
                if (str_contains($pathname, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                    continue;
                }
                if (str_contains($pathname, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                if (!preg_match($extPattern, $file->getFilename())) {
                    continue;
                }

                $latest = max($latest, (int) $file->getMTime());
            }
        }

        return $latest;
    }

    private function startChildPollingProcess(string $phpBinary, string $artisanPath): Process
    {
        $args = [
            $phpBinary,
            // Ensure unbuffered output so logs (e.g. --log-updates) are visible immediately when piped.
            '-d',
            'output_buffering=0',
            '-d',
            'zlib.output_compression=0',
            '-d',
            'implicit_flush=1',
            $artisanPath,
            'hybridgram:polling',
        ];

        $botId = $this->argument('botId');
        if ($botId !== null && $botId !== '') {
            $args[] = (string) $botId;
        }

        if ((bool) $this->option('log-updates')) {
            $args[] = '--log-updates';
        }
        if ((bool) $this->option('full')) {
            $args[] = '--full';
        }

        $process = new Process($args, base_path());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        // If we're attached to a TTY, use it to avoid CLI buffering in child process.
        try {
            if (Process::isTtySupported() && function_exists('posix_isatty') && posix_isatty(STDOUT)) {
                $process->setTty(true);
            }
        } catch (\Throwable) {
            // ignore
        }

        $process->start(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process;
    }

    private function stopChildProcess(Process $process): void
    {
        if (!$process->isRunning()) {
            return;
        }

        try {
            $process->stop(3, defined('SIGTERM') ? SIGTERM : null);
        } catch (\Throwable) {
            // ignore
        }
    }
}
