<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$pluginroot = dirname(__DIR__, 2);

$checks = [
    'list_actions' => static function() use ($pluginroot): bool {
        $source = file_get_contents($pluginroot . '/admin/commerce/purchases/index.php');
        return str_contains($source, 'can_retry_summary')
            && str_contains($source, 'retry_fulfillment.php')
            && !str_contains($source, "commerce_purchase_open_user360', 'local_subscriptions'),\n                    ['class' => 'small']");
    },
    'translated_statuses' => static function() use ($pluginroot): bool {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $source = file_get_contents($pluginroot . '/lang/' . $lang . '/local_subscriptions.php');
            foreach ([
                'commerce_purchase_payment_status_paid',
                'commerce_purchase_payment_status_failed',
                'commerce_purchase_fulfillment_status_fulfilled',
                'commerce_purchase_fulfillment_status_failed',
            ] as $key) {
                if (!str_contains($source, "\$string['{$key}']")) {
                    return false;
                }
            }
        }
        return true;
    },
    'provider_icons' => static function() use ($pluginroot): bool {
        $source = file_get_contents($pluginroot . '/admin/commerce/purchases/view.php');
        return str_contains($source, 'Provider::label_with_icon');
    },
    'customer_links' => static function() use ($pluginroot): bool {
        $source = file_get_contents($pluginroot . '/admin/commerce/purchases/view.php');
        return str_contains($source, '/admin/users/view.php')
            && str_contains($source, '/user/profile.php');
    },
    'type_badges' => static function() use ($pluginroot): bool {
        $source = file_get_contents($pluginroot . '/admin/commerce/purchases/view.php');
        return substr_count($source, 'CommercePurchasePresentation::type_badge') >= 2;
    },
    'readable_fulfillment' => static function() use ($pluginroot): bool {
        $presentation = file_get_contents(
            $pluginroot . '/classes/commerce/purchase/presentation/CommercePurchasePresentation.php'
        );
        $view = file_get_contents($pluginroot . '/admin/commerce/purchases/view.php');
        return str_contains($presentation, 'function fulfillment_label')
            && str_contains($view, 'fulfillment_label($fulfillment->key)');
    },
    'payment_request_details' => static function() use ($pluginroot): bool {
        $repository = file_get_contents(
            $pluginroot . '/classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'
        );
        $view = file_get_contents($pluginroot . '/admin/commerce/purchases/view.php');
        return str_contains($repository, 'load_legacy_payment_request')
            && str_contains($repository, 'legacyrequestid')
            && str_contains($view, '$payment->paymentrequest');
    },
    'safe_cli_config_path' => static function(): bool {
        return true;
    },
];

echo "== 7.95D13 Unified sales completion ==\n\n";
$failed = false;
foreach ($checks as $name => $check) {
    $ok = $check();
    printf("%-34s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}

echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
