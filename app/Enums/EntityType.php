<?php

namespace App\Enums;

enum EntityType: string
{
    case HEAD_OFFICE = 'HEAD_OFFICE';
    case REGIONAL = 'REGIONAL';
    case UNIT = 'UNIT';

    public function label(): string
    {
        return match($this) {
            self::HEAD_OFFICE => 'Head Office',
            self::REGIONAL => 'Regional',
            self::UNIT => 'Unit',
        };
    }

    public function defaulLevel(): string
    {
        return match($this) {
            self::HEAD_OFFICE => 1,
            self::REGIONAL => 2,
            self::UNIT => 3,
        };
    }

    public function allowedParentType(): ?self
    {
        return match($this) {
            self::HEAD_OFFICE => null,
            self::REGIONAL => self::HEAD_OFFICE,
            self::UNIT => self::REGIONAL,
        };
    }

}
