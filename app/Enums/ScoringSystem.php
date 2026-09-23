<?php

declare(strict_types=1);

namespace App\Enums;

enum ScoringSystem: string
{
    case Points = 'points';
    case Ranking = 'ranking';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Points => 'Point system',
            self::Ranking => 'Ranking system',
            self::Other => 'Other scoring system',
        };
    }
}
