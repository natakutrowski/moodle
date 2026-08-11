<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions/';

$checks = [
    'fulfillment_action_uses_factory' => static function() use ($root): bool {
        $source = file_get_contents($root . 'admin/commerce/purchases/retry_fulfillment.php');

        return str_contains($source, 'CommercePurchaseActionServiceFactory::create()')
            && str_contains($source, '->process_fulfillment(')
            && !str_contains($source, 'new CommercePurchaseActionService()');
    },

    'repository_dependencies_injected' => static function() use ($root): bool {
        $source = file_get_contents(
            $root . 'classes/commerce/purchase/action/CommercePurchaseActionService.php'
        );

        return str_contains($source, 'new CommerceEntitlementGrantRepository(')
            && str_contains($source, '$this->db,')
            && str_contains($source, 'new CommerceEntitlementGrantRecordMapper()');
    },

    'initial_start_and_retry_distinguished' => static function() use ($root): bool {
        $view = file_get_contents($root . 'admin/commerce/purchases/view.php');
        $service = file_get_contents(
            $root . 'classes/commerce/purchase/action/CommercePurchaseActionService.php'
        );

        return str_contains($view, 'commerce_purchase_start_fulfillment')
            && str_contains($view, '$purchase->fulfillments === []')
            && str_contains($service, "'mode' => \$isinitial ? 'start' : 'retry'");
    },

    'missing_grants_reported_safely' => static function() use ($root): bool {
        $service = file_get_contents(
            $root . 'classes/commerce/purchase/action/CommercePurchaseActionService.php'
        );
        $controller = file_get_contents(
            $root . 'admin/commerce/purchases/retry_fulfillment.php'
        );

        return str_contains($service, "'missing_grants'")
            && str_contains($controller, 'commerce_purchase_fulfillment_missing_grants');
    },

    'purchase_status_dimensions' => static function() use ($root): bool {
        $source = file_get_contents(
            $root . 'classes/commerce/purchase/presentation/CommercePurchasePresentation.php'
        );

        foreach (['dimension_payment', 'dimension_order', 'dimension_delivery', 'dimension_access'] as $key) {
            if (!str_contains($source, 'commerce_purchase_' . $key)) {
                return false;
            }
        }

        return str_contains($source, '$totalminor === 0')
            && str_contains($source, 'commerce_purchase_payment_not_required')
            && str_contains($source, 'commerce_purchase_order_status_completed');
    },

    'native_admin_events_registered' => static function() use ($root): bool {
        $source = file_get_contents($root . 'classes/admin/AdminEvents.php');

        return str_contains($source, 'self::COMMERCE_PURCHASE_FULFILLMENT_RETRIED')
            && str_contains($source, 'self::COMMERCE_PURCHASE_NOTE_ADDED');
    },

    'upgrades_context_order' => static function() use ($root): bool {
        $source = file_get_contents($root . 'admin/plans/upgrades.php');
        $configure = strpos($source, 'CrmPageConfigurator::configure(');
        $format = strpos($source, 'format_string($plan->name)');

        return $configure !== false
            && $format !== false
            && $format > $configure;
    },

    'table_action_spacing' => static function() use ($root): bool {
        $plan = file_get_contents($root . 'admin/commerce/plans/view.php');
        $scope = file_get_contents($root . 'admin/commerce/products/access_scope.php');

        return substr_count($plan, 'btn btn-outline-primary mt-3') >= 2
            && str_contains($scope, 'd-flex gap-2 mt-3 mb-4 flex-wrap');
    },
];

mtrace('== 7.95E19D Purchase actions and status clarity ==');
mtrace('');

$failed = false;

foreach ($checks as $name => $check) {
    $ok = $check();
    mtrace(str_pad($name, 44) . ($ok ? 'OK' : 'FAILED'));
    $failed = $failed || !$ok;
}

mtrace('');
mtrace($failed ? '[FAILED]' : '[CERTIFIED]');

exit($failed ? 1 : 0);
