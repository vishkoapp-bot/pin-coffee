<?php
declare(strict_types=1);

namespace SeoEngine;

/**
 * PSR-4 compatible autoloader for the SeoEngine namespace.
 */
final class Autoloader
{
    private static string $baseDir;

    public static function register(string $baseDir): void
    {
        self::$baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        $prefix = 'SeoEngine\\';
        $prefixLen = strlen($prefix);

        if (strncmp($prefix, $class, $prefixLen) !== 0) {
            return;
        }

        $relativeClass = substr($class, $prefixLen);
        $file = self::$baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
}
