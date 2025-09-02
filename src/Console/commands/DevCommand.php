<?php

namespace maherremita\LaravelDev\Console\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use maherremita\LaravelDev\Services\DevService;

class DevCommand extends Command
{
    protected $signature = 'dev';

    protected $description = 'Launch all development commands in separate terminals';

    // Server manager instance
    protected DevService $serviceManager;

    // List of all commands
    protected array $commands;

    public function __construct(DevService $serviceManager)
    {
        parent::__construct();
        $this->serviceManager = $serviceManager;
        $this->commands = Config::get('laravel_dev.commands', []);
    }

    public function handle()
    {
        $commands = $this->commands;

        if (empty($commands)) {
            $this->error('No commands configured. Please publish and configure the config file.');
            $this->info('php artisan vendor:publish --provider="maherremita\LaravelDev\LaravelDevServiceProvider" --tag="config"');

            return self::FAILURE;
        }

        // start all development commands
        $this->startCommands();
        // run the command loop
        while (true) {
            // prompt the user for an action
            $action = $this->choice(
                'perform action',
                [
                    'start command',
                    'stop command',
                    'stop all commands',
                    'restart command',
                    'restart all commands',
                    'exit'
                ]
            );

            // start specific command
            if ($action === 'start command') {
                // Prompt the user to choose the command he wants to start
                $commandName = $this->choice(
                    'choose the command name you want to start',
                    array_keys($commands)
                );
                // Start the selected command
                $this->startCommand($commandName);
            }

            // Stop specific command
            if ($action === 'stop command') {
                // get the current running commands
                $runningCommands = $this->serviceManager->processes->pluck('name')->toArray();
                if (empty($runningCommands)) {
                    $this->error('No running commands to stop');

                    continue;
                }
                // prompt the user to choose the command he wants to stop
                $commandName = $this->choice(
                    'choose the command you want to stop',
                    $runningCommands
                );
                // Stop the selected command
                $this->stopCommand($commandName);
            }

            // stop all commands
            if ($action === 'stop all commands') {
                $this->stopCommands();
            }

            // restart command
            if ($action === 'restart command') {
                // get the current running commands
                $runningCommands = $this->serviceManager->processes->pluck('name')->toArray();
                if (empty($runningCommands)) {
                    $this->error('No running commands to restart');

                    continue;
                }

                // prompt the user to choose the command he wants to restart
                $commandName = $this->choice(
                    'choose the command you want to restart',
                    $runningCommands
                );
                // Restart the selected command
                $this->restartCommand($commandName);
            }

            // restart all commands
            if ($action === 'restart all commands') {
                $this->restartCommands();
            }

            // exit
            if ($action === 'exit') {
                $this->info('Exiting...');
                // stop all commands
                $this->stopCommands();

                return self::SUCCESS;
            }
        }
    }

    // start specific development command
    protected function startCommand(string $name): int
    {

        $this->info("🚀 Launching development command: {$name}...");
        $this->newLine();

        try {
            $this->serviceManager->startCommand($name);
            $this->info("✅ Development command '{$name}' has been launched!");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to launch development command '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    // Start all development commands
    protected function startCommands(): int
    {
        $this->info('🚀 Launching development commands...');
        $this->newLine();

        try {
            $this->serviceManager->startAllCommands();
            $this->info('✅ All development commands have been launched!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to launch development commands: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    // stop specific development command
    protected function stopCommand(string $name): int
    {
        $this->info("🛑 Stopping development command: {$name}...");
        $this->newLine();

        try {
            $this->serviceManager->stopCommand($name);
            $this->info("✅ Development command '{$name}' has been stopped!");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to stop development command '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    // Stop all development commands
    protected function stopCommands(): int
    {
        $this->info('🛑 Stopping all development commands...');

        try {
            $this->serviceManager->stopAllCommands();
            $this->info('✅ All development commands have been stopped!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to stop development commands: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    // restart specific development command
    protected function restartCommand(string $name): int
    {
        $this->info("🔄 Restarting development command: {$name}...");
        $this->newLine();

        try {
            $this->serviceManager->restartCommand($name);
            $this->info("✅ Development command '{$name}' has been restarted!");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to restart development command '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    // Restart all development commands
    protected function restartCommands(): int
    {
        $this->info('🔄 Restarting all development commands...');
        $this->newLine();

        try {
            $this->serviceManager->restartAllCommands();
            $this->info('✅ All development commands have been restarted!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to restart all development commands: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
