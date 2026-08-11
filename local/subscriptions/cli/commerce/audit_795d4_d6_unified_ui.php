<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListResult;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;

if (PHP_SAPI !== 'cli') { throw new moodle_exception('cli'); }

$pluginroot = dirname(__DIR__, 2);
$checks = [
    'list_filter_contract' => class_exists(CommercePurchaseListFilter::class) && class_exists(CommercePurchaseListResult::class),
    'native_read_repository' => str_contains(file_get_contents($pluginroot . '/classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'), 'CommercePersistenceSchema::TABLE_PURCHASE'),
    'server_pagination' => str_contains(file_get_contents($pluginroot . '/classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'), 'get_records_sql($sql, $params, $page * $perpage, $perpage)'),
    'unified_list_page' => is_file($pluginroot . '/admin/commerce/purchases/index.php'),
    'unified_view_page' => is_file($pluginroot . '/admin/commerce/purchases/view.php'),
    'presentation_layer' => class_exists(CommercePurchasePresentation::class),
    'navigation_entry' => str_contains(file_get_contents($pluginroot . '/classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php'), "self::PURCHASES"),
    'no_legacy_ui_dependency' => !preg_match('/subscription_plan|subscription_access_scope|tabs\/|plans_lib|scopes_lib/', file_get_contents($pluginroot . '/admin/commerce/purchases/index.php') . file_get_contents($pluginroot . '/admin/commerce/purchases/view.php')),
];

echo "== 7.95D4-D6 Unified Commerce sales UI ==\n\n";
$failed = false;
foreach ($checks as $label => $ok) { printf("%-30s %s\n", $label, $ok ? 'OK' : 'FAIL'); $failed = $failed || !$ok; }
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
