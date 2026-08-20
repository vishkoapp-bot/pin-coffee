<?php
declare(strict_types=1);

namespace SeoEngine\Services;

use SeoEngine\Config;
use SeoEngine\Models\Difficulty;
use SeoEngine\Models\KeywordData;

final class KeywordParser
{
    /**
     * Parse raw user input into keyword objects.
     * Supports pipe, tab, and comma delimiters.
     *
     * @return KeywordData[]
     */
    public static function parse(string $rawInput): array
    {
        $keywords = [];
        $seen     = [];
        $lines    = preg_split('/\r?\n/', trim($rawInput));

        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $separator = self::detectSeparator($line);
            $parts     = explode($separator, $line);

            if (count($parts) < 3) continue;

            $keyword = TextNormalizer::normalize(trim($parts[0]));
            if ($keyword === '') continue;

            $volumeRaw = TextNormalizer::persianToLatin(trim($parts[1]));
            $volumeInt = (int) preg_replace('/[^0-9]/', '', $volumeRaw);

            $diffText   = TextNormalizer::normalize(trim($parts[2]));
            $difficulty = Difficulty::parse($diffText);
            if ($difficulty === null) continue;

            $key = TextNormalizer::toLower($keyword);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $keywords[] = new KeywordData($keyword, $volumeInt, $difficulty);

            if (count($keywords) >= Config::MAX_KEYWORDS) break;
        }

        return $keywords;
    }

    private static function detectSeparator(string $line): string
    {
        if (str_contains($line, '|'))  return '|';
        if (str_contains($line, "\t")) return "\t";
        if (str_contains($line, ','))  return ',';
        return '|';
    }
}
