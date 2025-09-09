<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Development Commands
    |--------------------------------------------------------------------------
    |
    | Define the commands you want to run when launching your development environment.
    | Each command will run in its own terminal window.
    |
    | You can define commands in two ways:
    |
    | Simple format:
    | 'Command Name' => 'actual command'
    |
    | Advanced format with custom colors:
    | 'Command Name' => [
    |     'command' => 'actual command',
    |     'colors' => [
    |         'text' => 'text color',
    |         'background' => 'background color'
    |     ]
    | ]
    |
    | Available Windows Colors:
    | - Black, DarkBlue, DarkGreen, DarkCyan, DarkRed, DarkMagenta
    | - DarkYellow, Gray, DarkGray, Blue, Green, Cyan, Red, Magenta
    | - Yellow, White
    |
    | Available Linux Colors:
    | - Black, DarkBlue, DarkGreen, DarkCyan, DarkRed, DarkMagenta
    | - Gray, DarkGray, Blue, Green, Cyan, Red, Magenta, Yellow, White
    |
    | Available Mac Colors:
    | - black, white, red, green, blue, cyan, magenta, yellow
    |
    */
    'commands' => [
        'Laravel Server' => 'php artisan serve',
        'Queue Worker' => 'php artisan queue:work',

        // 'Vite Dev Server' => 'npm run dev --watch',
        // 'Reverb Server' => 'php artisan reverb:start',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic Commands
    |--------------------------------------------------------------------------
    |
    | Dynamic commands are generated at runtime based on data from the database
    | or other dynamic sources. Each entry contains PHP code as a string, which
    | is evaluated and must return an array of commands.
    |
    | Format:
    | 'identifier' => 'PHP code that returns an array of commands, like the "commands" array above'
    |
    | The PHP code must return an associative array in the format:
    | 'Command Name' => [   'shell command 1'
    |                       'shell command 2',
    |                       'shell command 3'
    |                    ]
    |
    |    or
    |
    | 'Command Name' => [
    |                       ['command 1' => 'shell command 1', 'colors' => [...]],
    |                       ['command 2' => 'shell command 2', 'colors' => [...]],
    |                       ['command 3' => 'shell command 3', 'colors' => [...]]
    |                    ]
    |
    | IMPORTANT:
    | - The code is executed with eval()
    | - Quotes must be escaped (\' or \")
    | - The code must end with a semicolon (;)
    |
    */
    'dynamic_commands' => [
        // 'colored_commands' => 'array_reduce(
        //         range(1, 3),
        //         function ($carry, $number) {
        //             $carry["Task {$number}"] = [
        //                 "command" => "echo Executing task {$number}",
        //                 "colors" => ["text" => "green", "background" => "black"]
        //             ];
        //             return $carry;
        //         },
        //         []
        //     );'
    ]
    
];
