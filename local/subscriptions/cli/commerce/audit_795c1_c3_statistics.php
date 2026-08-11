<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\statistics\CommerceStatisticsComparison;
use local_subscriptions\commerce\statistics\CommerceStatisticsFilter;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsService;

$period = CommerceStatisticsPeriod::last_days(30);
$checks = [];
$errors = [];

$checks['period_contract'] = $period->duration() === $period->previous()->duration();
$checks['comparison_contract'] = CommerceStatisticsComparison::compare(150, 100)->delta_percent() === 50.0;
$checks['currency_filter'] = (new CommerceStatisticsFilter('EUR'))->currency() === 'EUR';

try {
    $service = new CommerceStatisticsService(new CommerceStatisticsRepository($DB));
    $snapshot = $service->snapshot($period);
    $checks['native_repository'] = true;
    $checks['currency_isolation'] = true;
    foreach ($snapshot->metrics() as $metric) {
        if ($metric->currency() === null) {
            $checks['currency_isolation'] = false;
            break;
        }
    }
} catch (Throwable $exception) {
    $checks['native_repository'] = false;
    $checks['currency_isolation'] = false;
    $errors[] = $exception->getMessage();
}

$passed = !in_array(false, $checks, true);
echo "== 7.95C1-C3 Commerce statistics foundation ==\n\n";
foreach ($checks as $name => $ok) {
    printf("%-28s %s\n", $name, $ok ? 'OK' : 'FAIL');
}
if ($errors) {
    echo "\nErrors:\n- " . implode("\n- ", $errors) . "\n";
}
echo "\n[" . ($passed ? 'CERTIFIED' : 'FAILED') . "]\n";
exit($passed ? 0 : 1);
