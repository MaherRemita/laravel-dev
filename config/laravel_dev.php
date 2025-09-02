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

];
