<?php

use maherremita\LaravelDev\Services\DevService;

// This runs before each test in this file
beforeEach(function () {
    // Define constants that can be used in all tests
    $this->availableCommands = ['Laravel Server', 'Queue Worker', 'Vite Dev Server'];
 
    // Set a default config for the tests
    config(['laravel_dev.commands' => [
        'Laravel Server' => 'php artisan serve',
        'Queue Worker' => [
            'command' => 'php artisan serve',
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
    ]]);
});

test('it shows an error if no commands are configured', function () {
    // Override the config to be empty for this specific test
    config(['laravel_dev.commands' => []]);

    $this->artisan('dev')
        ->expectsOutput('No commands configured. Please publish and configure the config file.')
        ->expectsOutput('php artisan vendor:publish --provider="maherremita\LaravelDev\LaravelDevServiceProvider" --tag="config"')
        ->assertFailed();
});


test('it calls startAllCommands on launch and stopAllCommands on exit', function () {
    // Create the mock
    $mock = $this->mock(DevService::class);

    $mock->commands = config('laravel_dev.commands');
    $mock->processes = collect([
        ['name' => 'Laravel Server', 'id' => 1],
        ['name' => 'Queue Worker', 'id' => 2],
        ['name' => 'Vite Dev Server', 'id' => 3],
    ]);

    $mock->shouldReceive('startAllCommands')->once()->ordered();
    $mock->shouldReceive('stopAllCommands')->once()->ordered();

    // Simulate user input
    $this->artisan('dev')
    ->expectsChoice('perform action', 'exit', [
        'show all commands',
        'stop command',
        'stop all commands',
        'restart command',
        'restart all commands',
        'refresh commands',
        'exit'
    ])
    ->expectsOutput('Exiting...')
    ->assertSuccessful();

});
    
test('it can start a specific command', function () {
    // Create the mock
    $mock = $this->mock(DevService::class);

    $mock->commands = config('laravel_dev.commands');
    $mock->processes = collect([
        ['name' => 'Laravel Server', 'id' => 123],
    ]);

    $mock->shouldReceive('startAllCommands')->once()->ordered();
    $mock->shouldReceive('refreshCommands')->once()->ordered();
    $mock->shouldReceive('startCommand')->with('Queue Worker')->once()->ordered();
    $mock->shouldReceive('stopAllCommands')->once()->ordered();

    $actions = [
        'show all commands',
        'start command',
        'stop command',
        'stop all commands',
        'restart command',
        'restart all commands',
        'refresh commands',
        'exit'
    ];

    // Run the command and simulate a sequence of user inputs
    $this->artisan('dev')
        ->expectsChoice('perform action', 'start command', $actions)
        ->expectsChoice('choose the command name you want to start', 'Queue Worker', ['Laravel Server', 'Queue Worker', 'Vite Dev Server', 'Back'])
        ->expectsOutput('🚀 Launching development command: Queue Worker...')
        // We need a final action to exit the while(true) loop for the test to finish.
        ->expectsChoice('perform action', 'exit', $actions)
        ->expectsOutput('Exiting...')
        ->assertSuccessful();

});

test('it can stop a specific command', function () {
    // Create the mock and tell it that a process is "running".
    $mock = $this->mock(DevService::class);

    $mock->commands = config('laravel_dev.commands');
    $mock->processes = collect([
        ['name' => 'Laravel Server', 'id' => 123],
        ['name' => 'Queue Worker', 'id' => 456],
    ]);

    $actions = [
        'show all commands',
        'start command',
        'stop command',
        'stop all commands',
        'restart command',
        'restart all commands',
        'refresh commands',
        'exit'
    ];

    // Set expectations
    $mock->shouldReceive('startAllCommands')->once();
    $mock->shouldReceive('stopCommand')->with('Laravel Server')->once();
    $mock->shouldReceive('stopAllCommands')->once(); 

    // Simulate the user interaction
    $this->artisan('dev')
        ->expectsChoice('perform action', 'stop command', $actions)
        ->expectsChoice('choose the command you want to stop', 'Laravel Server', ['Laravel Server', 'Queue Worker', 'Back'])
        ->expectsOutput('🛑 Stopping development command: Laravel Server...')
        ->expectsChoice('perform action', 'exit', $actions)
        ->expectsOutput('Exiting...')
        ->assertSuccessful();
});

test('it can restart a specific command', function () {
    // Create the mock and tell it a process is running.
    $mock = $this->mock(DevService::class);

    $mock->commands = config('laravel_dev.commands');
    $mock->processes = collect([
        ['name' => 'Laravel Server', 'id' => 123],
        ['name' => 'Queue Worker', 'id' => 456],
    ]);

    // Set expectations
    $mock->shouldReceive('startAllCommands')->once();
    $mock->shouldReceive('restartCommand')->with('Laravel Server')->once();
    $mock->shouldReceive('stopAllCommands')->once();

    $actions = [
        'show all commands',
        'start command',
        'stop command',
        'stop all commands',
        'restart command',
        'restart all commands',
        'refresh commands',
        'exit'
    ];

    // Simulate the user interaction
    $this->artisan('dev')
        ->expectsChoice('perform action', 'restart command', $actions)
        ->expectsChoice('choose the command you want to restart', 'Laravel Server', ['Laravel Server', 'Queue Worker', 'Back'])
        ->expectsOutput('🔄 Restarting development command: Laravel Server...')
        ->expectsChoice('perform action', 'exit', $actions)
        ->expectsOutput('Exiting...')
        ->assertSuccessful();
});

test('it shows an error when trying to stop or restart a command if none are running', function () {
    // Create the mock and tell it that NO processes are running.
    $mock = $this->mock(DevService::class);

    $mock->commands = config('laravel_dev.commands');
    $mock->processes = collect([]); // Empty collection

    // Set expectations
    $mock->shouldReceive('startAllCommands')->once();
    $mock->shouldReceive('stopAllCommands')->once();
    // expect 'stopCommand' to NEVER be called.
    $mock->shouldNotReceive('stopCommand');
    $mock->shouldNotReceive('restartCommand');

    $actions = [
        'show all commands',
        'start command',
        'start all commands',
        'refresh commands',
        'exit'
    ];

    // Simulate the user interaction
    $this->artisan('dev')
        ->expectsChoice('perform action', 'stop command', $actions)
        ->expectsOutput('No running commands to stop')
        ->expectsChoice('perform action', 'restart command', $actions)
        ->expectsOutput('No running commands to restart')
        ->expectsChoice('perform action', 'exit', $actions)
        ->expectsOutput('Exiting...')
        ->assertSuccessful();
});
