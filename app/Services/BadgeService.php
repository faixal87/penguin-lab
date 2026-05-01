<?php

namespace App\Services;

class BadgeService
{
    public function forScore(float|int|string $score): string
    {
        $score = (float) $score;

        return match (true) {
            $score >= 90 => "\u{1F3C6} Linux Guru",
            $score >= 80 => "\u{1F947} Terminal Master",
            $score >= 70 => "\u{26A1} Shell Expert",
            $score >= 60 => "\u{1F9ED} Command Explorer",
            $score >= 50 => "\u{1F6E0} Linux Apprentice",
            default => "\u{1F4D8} Needs Practice",
        };
    }
}
