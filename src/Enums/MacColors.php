<?php

namespace maherremita\LaravelDev\Enums;

class MacColors
{
    public const VALID_COLORS = [
        'black',
        'white',
        'red',
        'green',
        'blue',
        'cyan',
        'magenta',
        'yellow',
    ];

    // Validate if a colors are supported
    public static function isValid(array $colors): bool
    {
        return isset($colors['text']) && isset($colors['background'])
            && in_array($colors['text'], self::VALID_COLORS)
            && in_array($colors['background'], self::VALID_COLORS);
    }
}
