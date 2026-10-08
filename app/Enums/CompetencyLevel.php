<?php

namespace App\Enums;

enum CompetencyLevel: int
{
    case Dasar = 1;
    case Novice = 2;
    case Kompeten = 3; 
    case Mahir = 4;
    case Ahli = 5;
    case OtoritasStrategis = 6;

    public function label(): string
    {
        return match ($this) {
            self::Dasar => 'Dasar',
            self::Novice => 'Novice',
            self::Kompeten => 'Kompeten',
            self::Mahir => 'Mahir',
            self::Ahli => 'Ahli',
            self::OtoritasStrategis => 'Otoritas Strategis'
        };
    }
}
