<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\statistics\CommerceStatisticsComparison;
use local_subscriptions\commerce\statistics\CommerceStatisticsMetric;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsSnapshot;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsDrilldown;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsPageRenderer;

$checks = [];
$checks['statistics_page'] = is_readable(__DIR__ . '/../../admin/commerce/statistics/index.php');
$checks['native_statistics_domain'] = class_exists(\local_subscriptions\commerce\statistics\CommerceStatisticsService::class);
$checks['navigation_integration'] = false;
foreach ((new CommerceSectionNavigationRegistry())->all_items() as $item) {
    if ($item->key === CommerceSectionNavigationRegistry::STATISTICS) {
        $checks['navigation_integration'] = str_contains($item->url->out(false), '/admin/commerce/statistics/index.php');
    }
}
$period = CommerceStatisticsPeriod::custom(1000, 2000);
$snapshot = new CommerceStatisticsSnapshot($period, $period->previous());
$snapshot->add(new CommerceStatisticsMetric('orders', CommerceStatisticsComparison::compare(3, 2), 'EUR'));
$snapshot->add(new CommerceStatisticsMetric('net_paid_minor', CommerceStatisticsComparison::compare(15000, 10000), 'EUR'));
$rendered = CommerceStatisticsPageRenderer::dashboard($snapshot);
$checks['currency_isolation'] = str_contains($rendered, 'EUR');
$checks['accessible_metric_links'] = str_contains($rendered, 'aria-label=');
$checks['drill_down'] = str_contains(CommerceStatisticsDrilldown::metric_url('failed_payments', 'EUR')->out(false), 'status=failed');
$checks['no_legacy_statistics_source'] = !str_contains(file_get_contents(__DIR__ . '/../../admin/commerce/statistics/index.php'), 'subscription_digital_payment_request');

$passed = !in_array(false, $checks, true);
echo "== 7.95C4-C6 Commerce statistics dashboard ==\n\n";
foreach ($checks as $name => $ok) {
    printf("%-30s %s\n", $name, $ok ? 'OK' : 'FAIL');
}
echo "\n[" . ($passed ? 'CERTIFIED' : 'FAILED') . "]\n";
exit($passed ? 0 : 1);
