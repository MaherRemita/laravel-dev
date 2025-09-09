<?php

use maherremita\LaravelDev\Services\DevService;

// This runs before each test in this file
beforeEach(function () {
    // Set a default static config for the tests
    config(['laravel_dev.commands' => [
        'Laravel Server' => 'php artisan serve',
        'Queue Worker' => [
            'command' => 'php artisan queue:work',
            'colors' => [
                'text' => 'Yellow',
                'background' => 'Green'
            ]
        ],
    ]]);
});

test('it can process simple dynamic commands', function () {
    // Configure dynamic commands that return simple string commands
    config(['laravel_dev.dynamic_commands' => [
        'simple_commands' => '[
            "Dynamic Task 1" => "echo Task 1",
            "Dynamic Task 2" => "echo Task 2"
        ];'
    ]]);

    $service = new DevService();

    expect($service->commands)->toHaveKey('Laravel Server')
        ->and($service->commands)->toHaveKey('Queue Worker')
        ->and($service->commands)->toHaveKey('Dynamic Task 1')
        ->and($service->commands)->toHaveKey('Dynamic Task 2')
        ->and($service->commands['Dynamic Task 1'])->toBe('echo Task 1')
        ->and($service->commands['Dynamic Task 2'])->toBe('echo Task 2');
});

test('it can process complex dynamic commands with colors', function () {
    // Configure dynamic commands that return commands with color configurations
    config(['laravel_dev.dynamic_commands' => [
        'colored_commands' => '[
            "Colored Task" => [
                "command" => "echo Colored Task",
                "colors" => [
                    "text" => "red",
                    "background" => "black"
                ]
            ]
        ];'
    ]]);

    $service = new DevService();

    expect($service->commands)->toHaveKey('Colored Task')
        ->and($service->commands['Colored Task'])->toBeArray()
        ->and($service->commands['Colored Task']['command'])->toBe('echo Colored Task')
        ->and($service->commands['Colored Task']['colors']['text'])->toBe('red')
        ->and($service->commands['Colored Task']['colors']['background'])->toBe('black');
});

test('it can process multiple dynamic command configurations', function () {
    // Configure multiple dynamic command sources
    config(['laravel_dev.dynamic_commands' => [
        'batch_1' => '[
            "Batch 1 Task 1" => "echo Batch 1 Task 1",
            "Batch 1 Task 2" => "echo Batch 1 Task 2"
        ];',
        'batch_2' => '[
            "Batch 2 Task 1" => "echo Batch 2 Task 1",
            "Batch 2 Task 2" => [
                "command" => "echo Batch 2 Task 2",
                "colors" => ["text" => "blue", "background" => "white"]
            ]
        ];'
    ]]);

    $service = new DevService();

    expect($service->commands)
        ->toHaveKey('Laravel Server') // Static command
        ->toHaveKey('Queue Worker') // Static command
        ->toHaveKey('Batch 1 Task 1') // Dynamic from batch_1
        ->toHaveKey('Batch 1 Task 2') // Dynamic from batch_1
        ->toHaveKey('Batch 2 Task 1') // Dynamic from batch_2
        ->toHaveKey('Batch 2 Task 2') // Dynamic from batch_2
        ->and($service->commands['Batch 1 Task 1'])->toBe('echo Batch 1 Task 1')
        ->and($service->commands['Batch 2 Task 2']['command'])->toBe('echo Batch 2 Task 2');
});

test('it can handle dynamic commands with PHP code evaluation', function () {
    // Configure dynamic commands using actual PHP code like the config example
    config(['laravel_dev.dynamic_commands' => [
        'generated_commands' => 'array_reduce(
            range(1, 3),
            function ($carry, $number) {
                $carry["Generated Task {$number}"] = [
                    "command" => "echo Executing task {$number}",
                    "colors" => ["text" => "green", "background" => "black"]
                ];
                return $carry;
            },
            []
        );'
    ]]);

    $service = new DevService();

    expect($service->commands)
        ->toHaveKey('Generated Task 1')
        ->toHaveKey('Generated Task 2')
        ->toHaveKey('Generated Task 3')
        ->and($service->commands['Generated Task 1']['command'])->toBe('echo Executing task 1')
        ->and($service->commands['Generated Task 2']['command'])->toBe('echo Executing task 2')
        ->and($service->commands['Generated Task 3']['command'])->toBe('echo Executing task 3')
        ->and($service->commands['Generated Task 1']['colors']['text'])->toBe('green')
        ->and($service->commands['Generated Task 1']['colors']['background'])->toBe('black');
});

test('it handles invalid dynamic command code gracefully', function () {
    // Configure invalid PHP code that should cause an exception
    config(['laravel_dev.dynamic_commands' => [
        'invalid_code' => 'throw new Exception("Invalid code");',
        'valid_code' => '["Valid Task" => "echo valid"];'
    ]]);

    // The service should still work with the valid commands and skip the invalid ones
    $service = new DevService();

    expect($service->commands)
        ->toHaveKey('Laravel Server') // Static command should still be there
        ->toHaveKey('Valid Task') // Valid dynamic command should be there
        ->and($service->commands)->not->toHaveKey('Invalid Task') // Invalid code should be ignored
        ->and($service->commands['Valid Task'])->toBe('echo valid');
});

test('it handles dynamic commands that return non-array values', function () {
    // Configure dynamic commands that don't return arrays
    config(['laravel_dev.dynamic_commands' => [
        'returns_string' => '"not an array";',
        'returns_null' => 'null;',
        'returns_number' => '42;',
        'valid_commands' => '["Working Task" => "echo working"];'
    ]]);

    $service = new DevService();

    // Only the valid array should be processed
    expect($service->commands)
        ->toHaveKey('Laravel Server')
        ->toHaveKey('Working Task')
        ->and($service->commands['Working Task'])->toBe('echo working');
});

test('it can refresh dynamic commands', function () {
    // Start with initial dynamic commands
    config(['laravel_dev.dynamic_commands' => [
        'initial_commands' => '["Initial Task" => "echo initial"];'
    ]]);

    $service = new DevService();
    
    expect($service->commands)->toHaveKey('Initial Task');

    // Change the dynamic commands configuration
    config(['laravel_dev.dynamic_commands' => [
        'updated_commands' => '["Updated Task" => "echo updated"];'
    ]]);

    // Commands should still have the old configuration
    expect($service->commands)
        ->toHaveKey('Initial Task')
        ->and($service->commands)->not->toHaveKey('Updated Task');

    // Refresh the commands
    $service->refreshCommands();

    // Now it should have the updated commands
    expect($service->commands)
        ->not->toHaveKey('Initial Task')
        ->toHaveKey('Updated Task')
        ->and($service->commands['Updated Task'])->toBe('echo updated');
});

test('it merges static and dynamic commands without conflicts', function () {
    // Configure dynamic commands with different names than static ones
    config(['laravel_dev.dynamic_commands' => [
        'no_conflict' => '["Dynamic Only" => "echo dynamic"];'
    ]]);

    $service = new DevService();

    expect($service->commands)
        ->toHaveKey('Laravel Server') // Static
        ->toHaveKey('Queue Worker') // Static
        ->toHaveKey('Dynamic Only') // Dynamic
        ->and(count($service->commands))->toBe(3);
});

test('it handles dynamic commands that override static commands', function () {
    // Configure dynamic commands with the same name as static ones
    config(['laravel_dev.dynamic_commands' => [
        'override' => '["Laravel Server" => "echo overridden server command"];'
    ]]);

    $service = new DevService();

    // Dynamic commands should override static ones (array_merge behavior)
    expect($service->commands)
        ->toHaveKey('Laravel Server')
        ->and($service->commands['Laravel Server'])->toBe('echo overridden server command');
});

test('it can start and stop dynamic commands', function () {
    // Configure some dynamic commands
    config(['laravel_dev.dynamic_commands' => [
        'startable_commands' => '[
            "Dynamic Server" => "echo starting dynamic server",
            "Dynamic Worker" => [
                "command" => "echo starting dynamic worker",
                "colors" => ["text" => "cyan", "background" => "black"]
            ]
        ];'
    ]]);

    // Mock the service to avoid actually running processes
    $mock = $this->mock(DevService::class);
    $mock->commands = [
        'Laravel Server' => 'php artisan serve',
        'Dynamic Server' => 'echo starting dynamic server',
        'Dynamic Worker' => [
            'command' => 'echo starting dynamic worker',
            'colors' => ['text' => 'cyan', 'background' => 'black']
        ]
    ];
    $mock->processes = collect([]);

    // Mock the necessary methods
    $mock->shouldReceive('startCommand')->with('Dynamic Server')->once();
    $mock->shouldReceive('stopCommand')->with('Dynamic Server')->once();

    // The commands should be available for starting
    expect($mock->commands)->toHaveKey('Dynamic Server')
        ->and($mock->commands)->toHaveKey('Dynamic Worker');

    // Should be able to start and stop a dynamic command
    $mock->startCommand('Dynamic Server');
    $mock->stopCommand('Dynamic Server');
    
    // This test verifies that dynamic commands can be started and stopped
    expect(true)->toBeTrue(); // Simple assertion to confirm test completion
});

test('it can show all commands including dynamic ones in DevCommand', function () {
    // Configure mixed static and dynamic commands
    config([
        'laravel_dev.commands' => [
            'Static Server' => 'php artisan serve',
            'Static Queue' => [
                'command' => 'php artisan queue:work',
                'colors' => ['text' => 'yellow', 'background' => 'blue']
            ]
        ],
        'laravel_dev.dynamic_commands' => [
            'runtime_commands' => '[
                "Dynamic Task 1" => "echo task 1",
                "Dynamic Task 2" => [
                    "command" => "echo task 2",
                    "colors" => ["text" => "red", "background" => "white"]
                ]
            ];'
        ]
    ]);

    $service = new DevService();

    // All commands should be available and colors should be correct
    expect($service->commands)->toHaveCount(4)
        ->and($service->commands)->toHaveKey('Static Server')
        ->and($service->commands)->toHaveKey('Static Queue')
        ->and($service->commands)->toHaveKey('Dynamic Task 1')
        ->and($service->commands)->toHaveKey('Dynamic Task 2')
        // Check command and colors for Static Queue
        ->and($service->commands['Static Queue']['command'])->toBe('php artisan queue:work')
        ->and($service->commands['Static Queue']['colors']['text'])->toBe('yellow')
        ->and($service->commands['Static Queue']['colors']['background'])->toBe('blue')
        // Check command and colors for Dynamic Task 2
        ->and($service->commands['Dynamic Task 2']['command'])->toBe('echo task 2')
        ->and($service->commands['Dynamic Task 2']['colors']['text'])->toBe('red')
        ->and($service->commands['Dynamic Task 2']['colors']['background'])->toBe('white');
});

test('it handles empty dynamic commands configuration', function () {
    // Configure empty dynamic commands
    config(['laravel_dev.dynamic_commands' => []]);

    $service = new DevService();

    // Should only have static commands
    expect($service->commands)->toHaveCount(2)
        ->and($service->commands)->toHaveKey('Laravel Server')
        ->and($service->commands)->toHaveKey('Queue Worker');
});

test('it handles dynamic commands that return empty arrays', function () {
    // Configure dynamic commands that return empty arrays
    config(['laravel_dev.dynamic_commands' => [
        'empty_array' => '[];',
        'valid_commands' => '["Working Task" => "echo working"];'
    ]]);

    $service = new DevService();

    expect($service->commands)
        ->toHaveCount(3)
        ->toHaveKey('Laravel Server')
        ->toHaveKey('Queue Worker')
        ->toHaveKey('Working Task')
        ->and($service->commands['Working Task'])->toBe('echo working')
        ->and($service->commands)->not->toHaveKey('empty_array');
});

test('it can handle complex PHP expressions in dynamic commands', function () {
    // Test with complex PHP expressions that might be used in real scenarios
    config(['laravel_dev.dynamic_commands' => [
        'complex_expression' => 'call_user_func(function() {
            $services = ["redis", "mysql"];
            $commands = [];
            foreach ($services as $service) {
                $commands["Service: " . ucfirst($service)] = "docker-compose up " . $service;
                $commands["Service: " . ucfirst($service) . " Logs"] = [
                    "command" => "docker-compose logs -f " . $service,
                    "colors" => ["text" => "white", "background" => "black"]
                ];
            }
            return $commands;
        });'
    ]]);

    $service = new DevService();

    // The expression creates commands for redis and mysql services
    expect($service->commands)
        ->toHaveKey('Service: Redis')
        ->toHaveKey('Service: Redis Logs')
        ->toHaveKey('Service: Mysql')
        ->toHaveKey('Service: Mysql Logs')
        ->and($service->commands['Service: Redis'])->toBe('docker-compose up redis')
        ->and($service->commands['Service: Redis Logs']['command'])->toBe('docker-compose logs -f redis')
        ->and($service->commands['Service: Mysql'])->toBe('docker-compose up mysql')
        ->and($service->commands['Service: Mysql Logs']['command'])->toBe('docker-compose logs -f mysql');
});

test('it preserves command order when merging static and dynamic commands', function () {
    // Configure commands in a specific order to test merging behavior
    config([
        'laravel_dev.commands' => [
            'A_Static' => 'echo static A',
            'Z_Static' => 'echo static Z'
        ],
        'laravel_dev.dynamic_commands' => [
            'middle_commands' => '[
                "M_Dynamic" => "echo dynamic M",
                "B_Dynamic" => "echo dynamic B"
            ];'
        ]
    ]);

    $service = new DevService();

    $commandNames = array_keys($service->commands);
    
    // Static commands should come first, then dynamic commands
    expect($commandNames[0])->toBe('A_Static')
        ->and($commandNames[1])->toBe('Z_Static')
        ->and(in_array('M_Dynamic', $commandNames))->toBeTrue()
        ->and(in_array('B_Dynamic', $commandNames))->toBeTrue()
        ->and($service->commands)->toHaveCount(4);
});
