<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\statistics\CommerceStatisticsFilter;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsQueryPolicy;
use local_subscriptions\commerce\statistics\CommerceStatisticsRequestCache;

$root = $CFG->dirroot . '/local/subscriptions';
$results = [];

$required = [
    'classes/commerce/statistics/CommerceStatisticsRequestCache.php',
    'classes/commerce/statistics/CommerceStatisticsQueryPolicy.php',
    'classes/commerce/statistics/CommerceStatisticsService.php',
    'classes/commerce/statistics/CommerceStatisticsSeriesService.php',
    'classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php',
];
$results['required_files'] = array_reduce(
    $required,
    static fn(bool $ok, string $file): bool => $ok && is_file($root . '/' . $file),
    true
);

$cache = new CommerceStatisticsRequestCache();
$calls = 0;
$cache->remember('audit', static function() use (&$calls): int {
    $calls++;
    return 1;
});
$cache->remember('audit', static function() use (&$calls): int {
    $calls++;
    return 2;
});
$results['request_cache'] = $calls === 1 && $cache->count() === 1;

$period = CommerceStatisticsPeriod::custom(1000, 2000);
$eur = new CommerceStatisticsFilter('EUR', 'stripe', 'sku-a');
$rub = new CommerceStatisticsFilter('RUB', 'stripe', 'sku-a');
$results['cache_isolation'] = CommerceStatisticsQueryPolicy::cache_key('aggregate', $period, $eur)
    !== CommerceStatisticsQueryPolicy::cache_key('aggregate', $period, $rub);
$results['query_bounds'] = CommerceStatisticsQueryPolicy::dashboard_days(9999) === 365
    && CommerceStatisticsQueryPolicy::top_products_limit(9999) === 20;

$renderer = file_get_contents(
    $root . '/classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php'
);
$results['accessible_tables'] = is_string($renderer)
    && str_contains($renderer, "html_writer::tag('caption'")
    && str_contains($renderer, "['scope' => 'col']")
    && str_contains($renderer, "['scope' => 'row']")
    && str_contains($renderer, "html_writer::tag('details'");

$service = file_get_contents($root . '/classes/commerce/statistics/CommerceStatisticsService.php');
$series = file_get_contents($root . '/classes/commerce/statistics/CommerceStatisticsSeriesService.php');
$results['services_use_cache'] = is_string($service)
    && is_string($series)
    && str_contains($service, 'CommerceStatisticsRequestCache')
    && str_contains($series, 'CommerceStatisticsRequestCache')
    && str_contains($service, '->remember(')
    && str_contains($series, '->remember(');

$repository = file_get_contents($root . '/classes/commerce/statistics/CommerceStatisticsRepository.php');
$results['native_only'] = is_string($repository)
    && str_contains($repository, 'local_subscriptions_commerce_purchase')
    && !str_contains($repository, '{local_subscriptions}')
    && !str_contains($repository, '{local_subscriptions_digital_purchase}');

$ok = !in_array(false, $results, true);

echo "== 7.95C10-C11 Commerce statistics final certification ==\n\n";
foreach ($results as $name => $valid) {
    printf("%-30s %s\n", $name, $valid ? 'OK' : 'FAIL');
}
echo "\n" . ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";

exit($ok ? 0 : 1);
