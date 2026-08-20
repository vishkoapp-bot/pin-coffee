<?php
declare(strict_types=1);

namespace SeoEngine\Models;

use SeoEngine\Config;

abstract class Difficulty
{
    public const Easy   = 'آسان';
    public const Medium = 'متوسط';
    public const Hard   = 'سخت';

    public static function toNumeric(string $difficulty): int
    {
        return Config::DIFFICULTY_NUMERIC[$difficulty] ?? 50;
    }

    public static function parse(string $text): ?string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        $map = [
            self::Easy   => ['آسان', 'easy', 'ساده', 'راحت', 'کم'],
            self::Medium => ['متوسط', 'medium', 'معمولی', 'نرمال'],
            self::Hard   => ['سخت', 'hard', 'difficult', 'دشوار', 'زیاد'],
        ];

        foreach ($map as $level => $aliases) {
            if (in_array($text, $aliases, true)) {
                return $level;
            }
        }

        return null;
    }
}
