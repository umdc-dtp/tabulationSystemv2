<?php

declare(strict_types=1);

namespace App\Enums;

enum TieRankingMethod: string
{
    case SkipPositions = 'skip_positions';
    case ConsecutivePositions = 'consecutive_positions';

    public function label(): string
    {
        return match ($this) {
            self::SkipPositions => 'Skip positions after a tie',
            self::ConsecutivePositions => 'Continue with the next position',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SkipPositions => 'Two tied for 1st are both 1st; the next participant is 3rd (1, 1, 3).',
            self::ConsecutivePositions => 'Two tied for 1st are both 1st; the next participant is 2nd (1, 1, 2).',
        };
    }
}
