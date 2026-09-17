<?php

namespace App\Enums;

enum BodLevel: int
{
    case BOD_1 = 1;
    case BOD_2 = 2;
    case BOD_3 = 3;
    case BOD_4 = 4;
    case BOD_5 = 5;
    case BOD_6 = 6;

    public function label(): string
    {
        return match ($this) {
            self::BOD_1 => 'BOD-1',
            self::BOD_2 => 'BOD-2',
            self::BOD_3 => 'BOD-3',
            self::BOD_4 => 'BOD-4',
            self::BOD_5 => 'BOD-5',
            self::BOD_6 => 'BOD-6',
        };
    }
}
