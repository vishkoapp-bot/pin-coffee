<?php
declare(strict_types=1);

require __DIR__ . '/../src/Autoloader.php';

use SeoEngine\Autoloader;
use SeoEngine\Models\KeywordData;
use SeoEngine\Analyzers\SeoAnalyzer;
use SeoEngine\Services\ContentStrategyGenerator;

Autoloader::register(__DIR__ . '/../src');

$sample = [
    new KeywordData('خرید گوشی موبایل', 5400, 'متوسط'),
    new KeywordData('چگونه عکس را ادیت کنیم', 1200, 'آسان'),
    new KeywordData('قیمت لپ تاپ گیمینگ', 2300, 'سخت'),
];

$analyzed = SeoAnalyzer::analyze($sample);
ContentStrategyGenerator::generate($analyzed);

$errors = [];

foreach ($analyzed as $kw) {
    // check that enhanced fields are populated
    if (empty($kw->metaDescription)) $errors[] = "metaDescription missing for {$kw->keyword}";
    if (empty($kw->suggestedHeadings)) $errors[] = "suggestedHeadings missing for {$kw->keyword}";
    if (empty($kw->serpFeatureTargets)) $errors[] = "serpFeatureTargets missing for {$kw->keyword}";
    if ($kw->readingTime <= 0) $errors[] = "readingTime not positive for {$kw->keyword}";
}

if (!empty($errors)) {
    echo "TEST FAILED:\n" . implode("\n", $errors) . "\n";
    exit(1);
}

// print summarized result
foreach ($analyzed as $kw) {
    echo "--- {$kw->keyword} ---\n";
    echo "Intent: {$kw->intent} | Funnel: {$kw->funnel} | Priority: {$kw->priorityScore}\n";
    echo "H1: {$kw->suggestedH1}\n";
    echo "Meta: {$kw->metaTitle} - {$kw->metaDescription}\n";
    echo "Headings: {$kw->suggestedHeadings}\n";
    echo "SERP Targets: {$kw->serpFeatureTargets}\n";
    echo "E-E-A-T: {$kw->eeatRecommendations}\n";
    echo "Questions: {$kw->targetQuestions}\n";
    echo "ReadingTime: {$kw->readingTime} min | WordCount target: {$kw->wordCount}\n";
    echo "Canonical: {$kw->canonical}\n\n";
}

echo "TEST PASSED\n";
exit(0);
