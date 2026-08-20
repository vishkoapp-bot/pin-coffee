<?php
declare(strict_types=1);

namespace SeoEngine\Services;

final class TextNormalizer
{
    private const CHAR_MAP = [
        'ك' => 'ک',
        'ي' => 'ی',
        'ى' => 'ی',
        "\xD9\xA0" => '۰',
    ];

    private const PERSIAN_DIGITS = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    private const LATIN_DIGITS   = ['0','1','2','3','4','5','6','7','8','9'];

    public static function normalize(string $text): string
    {
        $text = str_replace(
            array_keys(self::CHAR_MAP),
            array_values(self::CHAR_MAP),
            $text
        );
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    public static function slugify(string $text): string
    {
        $text = self::normalize($text);
        $text = str_replace(' ', '-', $text);
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text) ?? $text;
        $text = preg_replace('/-+/', '-', $text) ?? $text;
        return trim($text, '-');
    }

    public static function persianToLatin(string $text): string
    {
        return str_replace(self::PERSIAN_DIGITS, self::LATIN_DIGITS, $text);
    }

    public static function toLower(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Tokenize Persian text, removing stop words and short tokens.
     * @return string[]
     */
    public static function tokenize(string $text): array
    {
        $stopWords = ['و','در','با','از','به','برای','که','این','آن','را','تا','هم','یک','یا','هر','اما'];
        $words = explode(' ', self::toLower(self::normalize($text)));
        return array_values(array_filter(
            $words,
            fn(string $w) => !in_array($w, $stopWords, true) && mb_strlen($w) > 1
        ));
    }
}
