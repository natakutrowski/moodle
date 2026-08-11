<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';

$checks = [
    'series_domain' => [
        'classes/commerce/statistics/CommerceStatisticsSeries.php',
        'classes/commerce/statistics/CommerceStatisticsSeriesService.php',
    ],
    'native_queries' => [
        'classes/commerce/statistics/CommerceStatisticsRepository.php',
    ],
    'core_charts' => [
        'classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php',
    ],
    'dashboard_integration' => [
        'admin/commerce/statistics/index.php',
    ],
    'product_statistics' => [
        'admin/commerce/products/view.php',
    ],
    'accessible_fallback' => [
        'classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php',
    ],
];

$ok = true;

echo "== 7.95C7-C9 Commerce graphical statistics ==\n\n";

foreach ($checks as $name => $files) {
    $valid = true;

    foreach ($files as $file) {
        $valid = $valid && is_file($root . '/' . $file);
    }

    if ($name === 'core_charts' && $valid) {
        $content = file_get_contents($root . '/' . $files[0]);
        $valid = is_string($content)
            && str_contains($content, 'core\\chart_');
    }

    if ($name === 'accessible_fallback' && $valid) {
        $content = file_get_contents($root . '/' . $files[0]);

        $valid = is_string($content)
            && preg_match('/html_writer::tag\(\s*[\"\']details[\"\']/', $content) === 1
            && preg_match('/html_writer::tag\(\s*[\"\']table[\"\']/', $content) === 1
            && preg_match('/html_writer::tag\(\s*[\"\']caption[\"\']/', $content) === 1
            && preg_match('/[\"\']scope[\"\']\s*=>\s*[\"\']col[\"\']/', $content) === 1
            && preg_match('/[\"\']scope[\"\']\s*=>\s*[\"\']row[\"\']/', $content) === 1;
    }

    if ($name === 'native_queries' && $valid) {
        $content = file_get_contents($root . '/' . $files[0]);
        $valid = is_string($content)
            && str_contains($content, 'local_subscriptions_commerce_purchase');
    }

    printf("%-30s %s\n", $name, $valid ? 'OK' : 'FAIL');
    $ok = $ok && $valid;
}

echo "\n" . ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";

exit($ok ? 0 : 1);