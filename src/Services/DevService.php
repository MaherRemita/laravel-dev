<?php

namespace maherremita\LaravelDev\Services;

use Exception;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use maherremita\LaravelDev\Enums\LinuxColors;
use maherremita\LaravelDev\Enums\MacColors;
use maherremita\LaravelDev\Enums\WindowsColors;

class DevService
{
    // Collection of all running processes
    public Collection $processes;
    // command configurations
    public array $commands;

    // operating system family
    public string $operatingSystem = PHP_OS_FAMILY;

    // Constructor
    public function __construct()
    {
        $this->processes = collect();
        $this->commands = $this->getAllCommands();
    }

    // Get all commands (static + dynamic)
    protected function getAllCommands(): array
    {
        $staticCommands = Config::get('laravel_dev.commands', []);
        $dynamicCommands = $this->getDynamicCommands();

        return array_merge($staticCommands, $dynamicCommands);
    }

    protected function getDynamicCommands():array
    {
        $dynamicCommandsConfig = Config::get('laravel_dev.dynamic_commands', []);
        $dynamicCommands = [];

        foreach ($dynamicCommandsConfig as $key => $code) {
            try {
                // Evaluate the PHP code to get the dynamic commands
                $result = eval("return $code");
                if (is_array($result)) {
                    $dynamicCommands = array_merge($dynamicCommands, $result);
                }
            } catch (Exception $e) {
                // Logging is not available here; optionally handle the error silently or rethrow
            }
        }
        return $dynamicCommands;
    }

    // Refresh commands (useful when dynamic data changes)
    public function refreshCommands(): void
    {
        $this->commands = $this->getAllCommands();
    }

    // run process
    protected function runProcess(array $command): ProcessResult
    {
        // run the process
        $processResult = Process::run($command);
        // if the process fails, throw an exception
        if ($processResult->failed()) {
            throw new RuntimeException( $processResult->errorOutput());
        }
        // return the process result
        return $processResult;
    }

    // find command by name (from the config)
    protected function findCommand(string $name): array|string|null
    {
        $command = $this->commands[$name] ?? null;
        return $command;
    }

    // find a running process by name
    protected function findProcess(string $name): array
    {
        return $this->processes->where('name', $name)->first();
    }

    // check if the command is already running
    protected function isCommandRunning(string $name): bool
    {
        return $this->processes->contains('name', $name);
    }

    // start development command
    public function startCommand(string $name, bool $refresh = true): void
    {
        if ($refresh) {
            // Refresh commands to get latest dynamic commands
            $this->refreshCommands();
        }

        // get the command config
        $commandConfig = $this->findCommand($name);
        if (!$commandConfig) {
            throw new RuntimeException("Command '{$name}' is not configured");
        }

        // check if the command is aleady running
        if ($this->isCommandRunning($name)) {
            throw new RuntimeException("Development command '{$name}' is already running.");
        }

        // Parse command configuration
        $parsedConfig = $this->parseCommandConfig($commandConfig);
        // Start the process
        $this->startInNewTerminal($name, $parsedConfig['command'], $parsedConfig['colors']);
    }
    
    // Parse command configuration to handle both string and array formats
    protected function parseCommandConfig(array|string $commandConfig): array
    {
        if (is_string($commandConfig)) {
            return [
                'command' => $commandConfig,
                'colors' => []
            ];
        }

        if (is_array($commandConfig) && isset($commandConfig['command'])) {
            // convert the colors to lowercase if we are on mac os
            $commandConfig['colors'] = $this->operatingSystem === 'Darwin' ? array_map('strtolower', $commandConfig['colors'] ?? []) : $commandConfig['colors'] ?? [];
            // validate colors
            if (!$this->isColorsValid($commandConfig['colors'])) {
                throw new RuntimeException("Invalid colors");
            }

            return [
                'command' => $commandConfig['command'],
                'colors' => $commandConfig['colors'] ?? []
            ];
        }

        throw new RuntimeException('Invalid command configuration format');
    }

    // validate colors
    protected function isColorsValid(array $colors): bool
    {
        return match ($this->operatingSystem) {
            'Windows' => WindowsColors::isValid($colors),
            'Linux' => LinuxColors::isValid($colors),
            'Darwin' => MacColors::isValid($colors),
            default => throw new RuntimeException('Unsupported operating system: ' . $this->operatingSystem)
        };
    }

    // Start the development commands
    public function startAllCommands(): void
    {
        // Refresh commands to get latest dynamic commands
        $this->refreshCommands();
        $commands = $this->commands;
        foreach ($commands as $name => $command) {
            $this->startCommand($name, false);
        }
    }

    // Start a command in a new terminal window
    protected function startInNewTerminal(string $name, string $command, array $colors): void
    {
        // Build the terminal command
        $terminalCommand = $this->buildTerminalCommand($name, $command, $colors);

        // Run the process
        $process = $this->runProcess($terminalCommand);

        // Store the process information
        $this->processes->push([
            'name' => $name,
            'id' => (int)trim($process->output())
        ]);
    }

    // Build the terminal command based on the operating system
    protected function buildTerminalCommand(string $name, string $command, array $colors): array
    {
        return match ($this->operatingSystem) {
            'Windows' => $this->buildWindowsCommand($name, $command, $colors),
            'Linux' => $this->buildLinuxCommand($name, $command, $colors),
            'Darwin' => $this->buildMacCommand($name, $command, $colors),
            default => throw new RuntimeException('Unsupported operating system: ' . $this->operatingSystem)
        };
    }

    // Build the Windows command
    protected function buildWindowsCommand(string $name, string $command, array $colors): array
    {
        // Initialize colors script
        $colorsScript = "";

        // Validate colors & prepare the colors script
        if (!empty($colors)) {
            // Prepare the colors script
            $textColor = $colors['text'];
            $backgroundColor = $colors['background'];

            $colorsScript = "\$Host.UI.RawUI.BackgroundColor = ''$backgroundColor''; " .
                           "\$Host.UI.RawUI.ForegroundColor = ''$textColor''; " .
                           "\$env:NO_COLOR = ''1'';";
        }

        // Prepare inner command
        $innerCommand = "\$Host.UI.RawUI.WindowTitle = ''$name''; $colorsScript Clear-Host; $command";
        // Build the PowerShell script string
        $psScript = "\$process = Start-Process -FilePath powershell -ArgumentList '-NoExit', '-Command', '$innerCommand' -PassThru; Write-Output \$process.Id";

        // Return the terminal command
        return ['powershell', '-Command', $psScript];
    }

    // Build the Linux command
    protected function buildLinuxCommand(string $name, string $command, array $colors): array
    {
        // Initialize colors script
        $colorsScript = "";

        // Validate colors & prepare the colors script
        if (!empty($colors)) {
            // Prepare the colors script
            $textColor = $colors['text'];
            $backgroundColor = $colors['background'];

            $colorsScript = "echo -e '\033]11;{$backgroundColor}\007\\033]10;{$textColor}\007';";
        }

        // Create the bash script that handles pipe creation and PID extraction
        $bashScript = "pipe=\$(mktemp -u); mkfifo \$pipe; gnome-terminal --title '$name' -- bash -c \"echo \\$\\$ > \$pipe; $colorsScript $command;  exec bash\" & cat \$pipe; rm \$pipe";

        return [
            'bash',
            '-c',
            $bashScript
        ];
    }

    // Build the Mac command
    protected function buildMacCommand(string $name, string $command, array $colors): array
    {
        // tab variable name
        $tabName = 'tab_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name));
        // working directory
        $workingDirectory =  base_path();
        // base command
        $scriptCommand = [
            'osascript',
            '-e', 'tell application "Terminal"',
            '-e', 'activate',
            '-e', "set $tabName to do script \"clear && cd \\\"$workingDirectory\\\" && $command\"",
            '-e', "set custom title of $tabName to \"$name\"",
            '-e', 'end tell',
        ];

        // Prepare the colors script
        if (!empty($colors)) {
            $textColor = $colors['text'];
            $backgroundColor = $colors['background']; 

            // Insert color commands before 'end tell'
            array_splice($scriptCommand, -2, 0, [
                '-e', "set background color of $tabName to \"$textColor\"",
                '-e', "set normal text color of $tabName to \"$backgroundColor\""
            ]);
        }

        // add the commands for returning the new window ID
       array_splice($scriptCommand, -2, 0, [
           '-e', "delay 0.2",
           '-e', "set windowID to id of front window",
           '-e', "return windowID",
       ]);

        return $scriptCommand;
    }

    // stop development command
    public function stopCommand(string $name): void
    {
        // check if the command is running
        if (!$this->isCommandRunning($name)) {
            throw new RuntimeException("Development command '{$name}' is not running.");
        }
        // get the command id
        $commandId = (int)$this->findProcess($name)['id'];
        // stop the command
        match ($this->operatingSystem) {
            'Windows' => $this->stopWindowsCommand($commandId),
            'Linux' => $this->stopLinuxCommand($commandId),
            'Darwin' => $this->stopMacCommand($commandId),
            default => throw new RuntimeException('Unsupported operating system: ' . $this->operatingSystem)
        };
        // remove the command from the process list
        $this->processes = $this->processes->reject(fn($item) => $item['name'] === $name);
    }

    // stop windows command
    public function stopWindowsCommand(int $commandID): void
    {
        $command = ['taskkill', '/PID', $commandID, '/T', '/F'];
        $this->runProcess($command);
    }

    // stop linux command
    public function stopLinuxCommand(int $commandID): void
    {
        $command = ['kill', '-9', $commandID];
        $this->runProcess($command);
    }

    // Stop macOS command
    public function stopMacCommand(int $commandID): void
    {
        $command = [
            'osascript',
            '-e', 'tell application "Terminal"',
            '-e', "set targetWindowId to $commandID",
            '-e', 'if exists (window id targetWindowId) then',
            '-e', 'set targetWindow to window id targetWindowId',
            '-e', 'set targetTab to tab 1 of targetWindow',
            '-e', 'set targetTty to tty of targetTab',
            '-e', 'set ttyShort to text 9 thru -1 of targetTty',
            '-e', 'try',
            '-e', 'do shell script "ps -t " & ttyShort & " -o pid= | xargs kill -9"',
            '-e', 'end try',
            '-e', 'delay 0.5',
            '-e', 'close window id targetWindowId',
            '-e', 'end if',
            '-e', 'end tell'
        ];

        $this->runProcess($command);
    }

    // Stop all running processes
    public function stopAllCommands(): void
    {
        foreach ($this->processes as $process) {
            $this->stopCommand($process['name']);
        }
    }

    // Restart a specific development command
    public function restartCommand(string $name): void
    {
        $this->stopCommand($name);
        $this->startCommand($name);
    }

    // Restart all running commands
    public function restartAllCommands(): void
    {
        $this->stopAllCommands();
        $this->startAllCommands();
    }
}