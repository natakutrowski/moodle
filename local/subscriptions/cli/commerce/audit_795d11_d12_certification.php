<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;

if (PHP_SAPI !== 'cli') {
    throw new coding_exception('CLI only.');
}

$pluginroot = dirname(__DIR__, 2);
$checks = [];

$items = (new CommerceSectionNavigationRegistry())->all_items();
$keys = array_map(static fn($item): string => $item->key, $items);
$checks['unique_navigation'] = count($keys) === count(array_unique($keys))
    && array_count_values($keys)[CommerceSectionNavigationRegistry::PURCHASES] === 1;

$viewsource = file_get_contents($pluginroot . '/admin/commerce/purchases/view.php');
$indexsource = file_get_contents($pluginroot . '/admin/commerce/purchases/index.php');
$repositorysource = file_get_contents($pluginroot . '/classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php');
$presentationsource = file_get_contents($pluginroot . '/classes/commerce/purchase/presentation/CommercePurchasePresentation.php');

$checks['safe_identifier_string'] = !str_contains($viewsource, "get_string('id')")
    && str_contains($viewsource, "commerce_purchase_identifier");
$checks['user360_links'] = str_contains($viewsource, '/admin/users/view.php')
    && str_contains($indexsource, '/admin/users/view.php');
$checks['customer_name_fallback'] = str_contains($repositorysource, "get_records_list(")
    && str_contains($repositorysource, "'user'")
    && str_contains($repositorysource, '$firstname = $firstname !==');
$checks['type_badges'] = str_contains($presentationsource, 'function type_badge')
    && str_contains($indexsource, 'CommercePurchasePresentation::type_badge');
$checks['responsive_table'] = str_contains($indexsource, 'table-hover align-middle');
$checks['native_only'] = !preg_match('/subscription_plan|subscription_access_scope|tabs\/|lib\/.*_lib\.php/',
    $viewsource . $indexsource . $repositorysource);

mtrace('== 7.95D11-D12 Unified sales polish and certification ==');
mtrace('');
$failed = false;
foreach ($checks as $name => $ok) {
    mtrace(str_pad($name, 34) . ($ok ? 'OK' : 'FAIL'));
    $failed = $failed || !$ok;
}
mtrace('');
mtrace($failed ? '[FAILED]' : '[CERTIFIED]');
exit($failed ? 1 : 0);
