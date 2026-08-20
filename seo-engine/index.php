<?php
declare(strict_types=1);

/**
 * SEO Keyword Prioritization Engine v3.0
 *
 * Entry point — bootstraps the autoloader and runs the application.
 * Requires PHP 8.0+
 */

require_once __DIR__ . '/src/Autoloader.php';

SeoEngine\Autoloader::register(__DIR__ . '/src');

(new SeoEngine\Application(__DIR__))->run();
