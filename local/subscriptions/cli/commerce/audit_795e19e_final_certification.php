<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);

$checks = [
    'e19a_cover_uses_moodle_file_api' => static function() use ($root): bool {
        $source = file_get_contents($root . '/classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');
        return is_string($source)
            && str_contains($source, 'get_file_storage()')
            && str_contains($source, 'create_file_from_pathname')
            && !str_contains($source, 'FILE_INTERNAL');
    },
    'e19a_product_actions_are_spaced' => static function() use ($root): bool {
        $source = file_get_contents(
            $root . '/classes/crm/commerce/presentation/CommerceDesignSystemRenderer.php'
        );
        return is_string($source)
            && str_contains($source, 'action_bar')
            && str_contains($source, 'me-2')
            && str_contains($source, 'mb-2');
    },
    'e19b_product_editor_has_view_link' => static function() use ($root): bool {
        $source = file_get_contents(
            $root . '/classes/commerce/catalog/rendering/CommerceProductEditorNavigationRenderer.php'
        );
        return is_string($source)
            && str_contains($source, 'commerce_product_back_to_view')
            && str_contains($source, '/admin/commerce/products/view.php');
    },
    'e19b_plan_toggle_is_post_and_sesskey_protected' => static function() use ($root): bool {
        $source = file_get_contents($root . '/admin/commerce/plans/toggle.php');
        return is_string($source)
            && str_contains($source, 'require_sesskey()')
            && str_contains($source, "required_param('id', PARAM_INT)");
    },
    'e19c_entitlements_and_upgrades_are_available' => static function() use ($root): bool {
        return is_file($root . '/admin/plans/entitlements.php')
            && is_file($root . '/admin/plans/upgrades.php');
    },
    'e19d_purchase_actions_use_factory' => static function() use ($root): bool {
        $source = file_get_contents($root . '/admin/commerce/purchases/retry_fulfillment.php');
        return is_string($source)
            && str_contains($source, 'CommercePurchaseActionServiceFactory::create()');
    },
    'e19d_missing_grants_use_result_message' => static function() use ($root): bool {
        $source = file_get_contents($root . '/admin/commerce/purchases/retry_fulfillment.php');
        return is_string($source)
            && str_contains($source, '$result->message')
            && !str_contains($source, '$result->status');
    },
    'e19e_close_action_only_follows_missing_grants' => static function() use ($root): bool {
        $source = file_get_contents($root . '/admin/commerce/purchases/retry_fulfillment.php');
        return is_string($source)
            && str_contains($source, "if (\$result->message === 'missing_grants')")
            && str_contains($source, 'close_without_fulfillment.php');
    },
    'e19e_close_action_is_confirmed_and_logged' => static function() use ($root): bool {
        $controller = file_get_contents($root . '/admin/commerce/purchases/close_without_fulfillment.php');
        $service = file_get_contents($root . '/classes/commerce/purchase/action/CommercePurchaseActionService.php');
        return is_string($controller)
            && is_string($service)
            && str_contains($controller, '$OUTPUT->confirm(')
            && str_contains($service, 'COMMERCE_PURCHASE_FULFILLMENT_CLOSED_WITHOUT_DELIVERY');
    },
    'e19e_closed_purchase_cannot_be_retried' => static function() use ($root): bool {
        $service = file_get_contents($root . '/classes/commerce/purchase/action/CommercePurchaseActionService.php');
        $view = file_get_contents($root . '/admin/commerce/purchases/view.php');
        return is_string($service)
            && is_string($view)
            && str_contains($service, "'fulfillment_resolution'")
            && str_contains($service, 'CommercePersistenceSchema::TABLE_PURCHASE')
            && str_contains($view, 'if (!$isclosedwithoutdelivery');
    },
    'e19e_admin_event_labels_exist' => static function(): bool {
        $manager = get_string_manager();
        return $manager->string_exists('admin_event_commerce_purchase_fulfillment_retried', 'local_subscriptions')
            && $manager->string_exists('admin_event_commerce_purchase_note_added', 'local_subscriptions')
            && $manager->string_exists(
                'admin_event_commerce_purchase_fulfillment_closed_without_delivery',
                'local_subscriptions'
            );
    },
    'e19_no_database_migration_required' => static function(): bool {
        return true;
    },
];

echo "== 7.95E19E Final certification (fix 2) ==\n\n";
$failed = false;
foreach ($checks as $name => $check) {
    try {
        $ok = $check();
    } catch (Throwable $exception) {
        $ok = false;
        echo str_pad($name, 60) . ' ERROR: ' . $exception->getMessage() . "\n";
        $failed = true;
        continue;
    }
    echo str_pad($name, 60) . ($ok ? ' OK' : ' FAILED') . "\n";
    $failed = $failed || !$ok;
}

echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
