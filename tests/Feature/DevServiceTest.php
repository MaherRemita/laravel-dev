<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use maherremita\LaravelDev\Services\DevService;
// use RuntimeException;

// This runs before each test in this file, ensuring a clean state.
beforeEach(function () {
    // standard configuration for the service to use.
    Config::set('laravel_dev.commands', [
        'Laravel Server' => 'php artisan serve',
        'Queue Worker' => [
            'command' => 'php artisan queue:work',
            'colors' => [
                'text' => 'Yellow',
                'background' => 'Green'
            ]
        ],
        'Vite Dev Server' => [
            'command' => 'npm run dev --watch',
            'colors' => [
                'text' => 'Black',
                'background' => 'Cyan'
            ]
        ],
    ]);

    Process::fake([
        // For any command, pretend it ran successfully and returned '12345' as its output.
        // We'll use this as our fake Process ID (PID).
        '*' => Process::result(output: '12345'),
    ]);

    // initialize the service
    $this->service = new DevService();
});


// --- Test Group: Command starting ---
test('it can start a single command and track its process', function () {
    // Start the Laravel Server command
    $this->service->startCommand('Laravel Server');

    // Assert that the service is now tracking the new process.
    expect($this->service->processes)->toHaveCount(1);
    $processInfo = $this->service->processes->first();
    expect($processInfo['name'])->toBe('Laravel Server');
    expect($processInfo['id'])->toBe(12345); // The fake ID we faked.
});

test('it can start all configured commands', function () {

    $this->service->startAllCommands();

    expect($this->service->processes)->toHaveCount(3);
    $processesInfo = $this->service->processes;
    expect($processesInfo)->toEqual(collect([
        [
            'name' => 'Laravel Server',
            'id' => 12345
        ],
        [
            'name' => 'Queue Worker',
            'id' => 12345
        ],
        [
            'name' => 'Vite Dev Server',
            'id' => 12345
        ]
    ]));
});

test('it throws an exception when starting a command that is not configured', function () {
    // Attempt to start a non-existent command
    $this->service->startCommand('NonExistentCommand');

})->throws(RuntimeException::class, "Command 'NonExistentCommand' is not configured");

test('it throws an exception when starting a command that is already running', function () {
    // Start it once.
    $this->service->startCommand('Laravel Server');
    // Try to start it again.
    $this->service->startCommand('Laravel Server');

})->throws(RuntimeException::class, "Development command 'Laravel Server' is already running.");

test('it parses command configuration correctly', function () {
    // Use Reflection to get the protected method.
    $method = new ReflectionMethod(DevService::class, 'parseCommandConfig');
    // Make it accessible for this test.
    $method->setAccessible(true);
    // parse simple command config
    $simpleParsedConfig = $method->invoke($this->service, 'php artisan serve');
    // parse complex command config
    $complexParsedConfig = $method->invoke($this->service, [
        'command' => 'php artisan serve',
        'colors' => [
            'text' => 'Yellow',
            'background' => 'Green'
        ]
    ]);

    expect($simpleParsedConfig)->toEqual([
        'command' => 'php artisan serve',
        'colors' => []
    ]);

    expect($complexParsedConfig)->toEqual([
        'command' => 'php artisan serve',
        'colors' => [
            'text' => $this->service->operatingSystem === 'Darwin' ? 'yellow' : 'Yellow',
            'background' => $this->service->operatingSystem === 'Darwin' ? 'green' : 'Green'
        ]
    ]);
});

test('it throws an exception if colors are not valid', function () {
    // Use Reflection to get the protected method.
    $method = new ReflectionMethod(DevService::class, 'parseCommandConfig');
    // Make it accessible for this test.
    $method->setAccessible(true);

    // parse command config
    $method->invoke($this->service, [
        'command' => 'php artisan serve',
        'colors' => [
            'text' => 'invalidColor',
            'background' => 'invalidColor'
        ]
    ]);
})->throws(RuntimeException::class, "Invalid colors");


// --- Test Group: Command Stopping ---
test('it can stop a running command', function () {
    // Start the command
    $this->service->startCommand('Laravel Server');
    // stop the command
    $this->service->stopCommand('Laravel Server');

    // Assert that the process is no longer tracked.
    expect($this->service->processes)->toBeEmpty();
});

test('it can stop all running commands', function () {
    // Manually set up the "running" processes for the service.
    $this->service->processes = collect([
        ['name' => 'Laravel Server', 'id' => 12345],
        ['name' => 'Queue Worker', 'id' => 12345],
        ['name' => 'Vite Dev Server', 'id' => 12345]
    ]);
    // Stop all commands
    $this->service->stopAllCommands();

    // Assert that the process is no longer tracked.
    expect($this->service->processes)->toBeEmpty();
});

test('it throws an exception when stopping a command that is not running', function () {
    // set up the processes collection
    $this->service->processes = collect();
    // attempt to stop a command that is not running
    $this->service->stopCommand('Laravel Server');

})->throws(RuntimeException::class, "Development command 'Laravel Server' is not running.");


// --- Test Group: Command Restarting ---
test('it can restart a single command', function () {
    // Start the command
    $this->service->startCommand('Laravel Server');
    // restart the command
    $this->service->restartCommand('Laravel Server');

    // Assert that the restart logic calls stop and then start.
    expect($this->service->processes)->toEqual(collect([
        [
            'name' => 'Laravel Server',
            'id' => 12345
        ]
    ]));
});

test('it can restart all commands', function () {
    // set up the processes collection
    $this->service->processes = collect([
        ['name' => 'Laravel Server', 'id' => 12345],
        ['name' => 'Queue Worker', 'id' => 12345],
        ['name' => 'Vite Dev Server', 'id' => 12345]
    ]);
    // restart all commands
    $this->service->restartAllCommands();

    // Assert that the restart logic calls stop and then start for all.
    expect($this->service->processes)->toEqual(collect([
        ['name' => 'Laravel Server', 'id' => 12345],
        ['name' => 'Queue Worker', 'id' => 12345],
        ['name' => 'Vite Dev Server', 'id' => 12345]
    ]));
});


// --- Test Group: OS-Specific Commands ---
test('it runs the correct start process command for Windows - simple command', function () {
    // set os to windows
    $this->service->operatingSystem = 'Windows';
    // start simple configured command (without colors)
    $this->service->startCommand('Laravel Server');
    // Assert that the correct process was started
    Process::assertRan(function ($process) {
        // check that the command is correct
        return $process->command == [
            "powershell",
            "-Command",
            "\$process = Start-Process -FilePath powershell -ArgumentList '-NoExit', '-Command', '\$Host.UI.RawUI.WindowTitle = ''Laravel Server'';  Clear-Host; php artisan serve' -PassThru; Write-Output \$process.Id"
        ];
    });
});

test('it runs the correct start process command for Windows - complex command with colors', function () {
    // set os to windows
    $this->service->operatingSystem = 'Windows';
    // start complex configured command (with colors)
    $this->service->startCommand('Queue Worker');
    // Assert that the correct process was started
    Process::assertRan(function ($process) {
        // check that the command is correct
        return $process->command == [
            "powershell",
            "-Command",
            "\$process = Start-Process -FilePath powershell -ArgumentList '-NoExit', '-Command', '\$Host.UI.RawUI.WindowTitle = ''Queue Worker''; \$Host.UI.RawUI.BackgroundColor = ''Green''; \$Host.UI.RawUI.ForegroundColor = ''Yellow''; \$env:NO_COLOR = ''1''; Clear-Host; php artisan queue:work' -PassThru; Write-Output \$process.Id"
        ];
    });
});

test('it runs the correct start process command for Linux - simple command', function () {
    // set os to linux
    $this->service->operatingSystem = 'Linux';

    $this->service->startCommand('Laravel Server');

    Process::assertRan(function ($process) {
        return $process->command == [
            'bash',
            '-c',
            'pipe=$(mktemp -u); mkfifo $pipe; gnome-terminal --title \'Laravel Server\' -- bash -c "echo \$\$ > $pipe;  php artisan serve;  exec bash" & cat $pipe; rm $pipe'
        ];
    });
});

test('it runs the correct start process command for Linux - complex command with colors', function () {
    // set os to linux
    $this->service->operatingSystem = 'Linux';
    // start complex configured command (with colors)
    $this->service->startCommand('Queue Worker');

    Process::assertRan(function ($process) {
        return $process->command == [
            "bash",
            "-c",
            "pipe=\$(mktemp -u); mkfifo \$pipe; gnome-terminal --title 'Queue Worker' -- bash -c \"echo \\$\\$ > \$pipe; echo -e '\033]11;Green\007\\033]10;Yellow\007'; php artisan queue:work;  exec bash\" & cat \$pipe; rm \$pipe"
        ];
    });
});

test('it runs the correct start process command for macOS - simple command', function () {
    // set os to mac
    $this->service->operatingSystem = 'Darwin';
    // start simple configured command (without colors)
    $name = 'Laravel Server';
    $tabName = 'tab_laravel_server';

    $this->service->startCommand($name);

    Process::assertRan(function ($process) use ($tabName) {
        // working directory
        $workingDirectory =  base_path();

        return $process->command == [
            'osascript',
            '-e', 'tell application "Terminal"',
            '-e', 'activate',
            '-e', "set $tabName to do script \"clear && cd \\\"$workingDirectory\\\" && php artisan serve\"",
            '-e', "set custom title of $tabName to \"Laravel Server\"",
            '-e', "delay 0.2",
            '-e', "set windowID to id of front window",
            '-e', "return windowID",
            '-e', 'end tell',
        ];
    });
});

test('it runs the correct start process command for for macOS - complex command with colors', function () {
    // set os to mac
    $this->service->operatingSystem = 'Darwin';
    // start complex configured command (with colors)
    $name = 'Queue Worker';
    $tabName = 'tab_queue_worker';

    $this->service->startCommand($name);

    Process::assertRan(function ($process) use ($tabName) {
        // dd($process->command);
        // working directory
        $workingDirectory =  base_path();
        
        return $process->command == [
            'osascript',
            '-e', 'tell application "Terminal"',
            '-e', 'activate',
            '-e', "set $tabName to do script \"clear && cd \\\"$workingDirectory\\\" && php artisan queue:work\"",
            '-e', "set custom title of $tabName to \"Queue Worker\"",
            '-e', "set background color of $tabName to \"yellow\"",
            '-e', "set normal text color of $tabName to \"green\"",
            '-e', "delay 0.2",
            '-e', "set windowID to id of front window",
            '-e', "return windowID",
            '-e', 'end tell',
        ];
    });
});

test('it runs the correct stop process command for Windows', function () {
    // set os to windows
    $this->service->operatingSystem = 'Windows';
    // initiate the running commands 
    $this->service->processes = collect([
        [
            'name' => 'Laravel Server',
            'id' => 12345
        ]
    ]);
    // stop command
    $this->service->stopCommand('Laravel Server');

    // Assert that the correct process was started
    Process::assertRan(function ($process) {
        // check that the command is correct
        return $process->command == ['taskkill', '/PID', 12345, '/T', '/F'];
    });
});

test('it runs the correct stop process command for linux', function () {
    // set os to linux
    $this->service->operatingSystem = 'Linux';
    // initiate the running commands
    $this->service->processes = collect([
        [
            'name' => 'Laravel Server',
            'id' => 12345
        ]
    ]);
    // stop command
    $this->service->stopCommand('Laravel Server');

    // Assert that the correct process was started
    Process::assertRan(function ($process) {
        // check that the command is correct
        return $process->command == ['kill', '-9', 12345];
    });
});

test('it runs the correct stop process command for macOS', function () {
    // set os to mac
    $this->service->operatingSystem = 'Darwin';
    // initiate the running commands
    $this->service->processes = collect([
        [
            'name' => 'Laravel Server',
            'id' => 12345
        ]
    ]);
    // stop command
    $this->service->stopCommand('Laravel Server');

    // Assert that the correct process was started
    Process::assertRan(function ($process) {
        // check that the command is correct
        return $process->command == [
            'osascript',
            '-e', 'tell application "Terminal"',
            '-e', "set targetWindowId to 12345",
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
    });
});
