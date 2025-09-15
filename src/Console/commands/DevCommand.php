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
        // Get all commands (static + dynamic) from the service manager
        $this->commands = $this->serviceManager->commands;
    }
    
    public function handle()
    {
        if (empty($this->commands)) {
            $this->error('No commands configured. Please publish and configure the config file.');
            $this->info('php artisan vendor:publish --provider="maherremita\LaravelDev\LaravelDevServiceProvider" --tag="config"');

            return self::FAILURE;
        }

        $this->info('📋 Development Command Manager');
        $this->info('Available commands: ' . count($this->commands));
        $this->newLine();

        // start all development commands
        $this->startCommands();
        // run the command loop
        while (true) {
            // prompt the user for an action
            $action = $this->choice(
                'perform action',
                $this->getActions()
            );

            // Show all available commands
            if ($action === 'show all commands') {
                $this->showAllCommands();
            }

            // start specific command
            if ($action === 'start command') {
                // Refresh commands to get latest dynamic commands
                $this->refreshAvailableCommands();
                // Prompt the user to choose the command he wants to start
                $commandName = $this->choice(
                    'choose the command name you want to start',
                    array_merge(array_keys($this->commands), ['Back'])
                );
                // If the user chose 'Back', skip starting any command
                if ($commandName === 'Back') {
                    continue;
                }
                // Start the selected command
                $this->startCommand($commandName);
            }

            // start all commands
            if ($action === 'start all commands') {
                $this->startCommands();
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
                    array_merge($runningCommands, ['Back'])
                );
                // If the user chose 'Back', skip stopping any command
                if ($commandName === 'Back') {
                    continue;
                }
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
                    array_merge($runningCommands, ['Back'])
                );
                // If the user chose 'Back', skip restarting any command
                if ($commandName === 'Back') {
                    continue;
                }
                // Restart the selected command
                $this->restartCommand($commandName);
            }

            // restart all commands
            if ($action === 'restart all commands') {
                $this->restartCommands();
            }

            // refresh commands manually
            if ($action === 'refresh commands') {
                $this->refreshAvailableCommands();
                $this->info('✅ Commands refreshed! Available commands: ' . count($this->commands));
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

    // get the actions dynamically
    protected function getActions(): array
    {
        $actions = [
            'show all commands',
            'start command',
            'start all commands',
            'stop command',
            'stop all commands',
            'restart command',
            'restart all commands',
            'refresh commands',
            'exit'
        ];

        // if there are no running commands, remove stop and restart actions
        if ($this->serviceManager->processes->isEmpty()) {
            $actions = array_diff($actions, ['stop command', 'stop all commands', 'restart command', 'restart all commands']);
        
        //else if there are running commands, remove start all commands action
        } else {
            $actions = array_diff($actions, ['start all commands']); 
        }

        // if all commands running remove the start commands action
        if ($this->serviceManager->processes->count() === count($this->commands)) {
            $actions = array_diff($actions, ['start command']);
        }

        // Re-index the array
        $actions = array_values($actions);

        return $actions;
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

    // Show all available commands
    protected function showAllCommands(): void
    {
        $this->refreshAvailableCommands();
        
        $this->info('📋 Available Commands:');
        $this->newLine();

        $staticCommands = Config::get('laravel_dev.commands', []);
        $allCommands = $this->commands;

        // Show static commands
        if (!empty($staticCommands)) {
            $this->info('🔧 Static Commands:');
            foreach ($staticCommands as $name => $command) {
                $commandText = is_array($command) ? $command['command'] : $command;
                $shortCommand = strlen($commandText) > 60 ? substr($commandText, 0, 57) . '...' : $commandText;
                $this->line("  • {$name}: {$shortCommand}");
            }
            $this->newLine();
        }

        // Show dynamic commands
        $dynamicCommands = array_diff_key($allCommands, $staticCommands);
        if (!empty($dynamicCommands)) {
            $this->info('🔄 Dynamic Commands:');
            foreach ($dynamicCommands as $name => $command) {
                $commandText = is_array($command) ? $command['command'] : $command;
                $shortCommand = strlen($commandText) > 60 ? substr($commandText, 0, 57) . '...' : $commandText;
                $this->line("  • {$name}: {$shortCommand}");
            }
            $this->newLine();
        }
    }

    // Refresh available commands
    protected function refreshAvailableCommands(): void
    {
        $this->serviceManager->refreshCommands();
        $this->commands = $this->serviceManager->commands;
    }
    
}
