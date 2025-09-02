<?php

namespace maherremita\LaravelDev\Enums;

class WindowsColors
{
    public const VALID_COLORS = [
        'Black',
        'DarkBlue',
        'DarkGreen',
        'DarkCyan',
        'DarkRed',
        'DarkMagenta',
        'DarkYellow',
        'Gray',
        'DarkGray',
        'Blue',
        'Green',
        'Cyan',
        'Red',
        'Magenta',
        'Yellow',
        'White',
    ];

    // Validate if a colors are supported
    public static function isValid(array $colors): bool
    {
        return isset($colors['text']) && isset($colors['background'])
            && in_array($colors['text'], self::VALID_COLORS)
            && in_array($colors['background'], self::VALID_COLORS);
    }
}
