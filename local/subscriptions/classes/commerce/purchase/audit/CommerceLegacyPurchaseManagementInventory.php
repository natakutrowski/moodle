<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\audit;

defined('MOODLE_INTERNAL') || die();

/** Explicit inventory of the historical subscription configuration boundary. */
final class CommerceLegacyPurchaseManagementInventory {
    public static function files(): array {
        return [
            'admin/manage.php',
            'lib/plans_lib.php',
            'lib/scopes_lib.php',
            'lib/user_subs_lib.php',
            'tabs/plans.php',
            'tabs/scopes.php',
            'tabs/user_subscriptions.php',
        ];
    }

    public static function tables(): array {
        return [
            'subscription_access_scope',
            'subscription_access_scope_translation',
            'subscription_plan',
            'subscription_plan_translation',
            'subscription_plan_price',
            'subscription_plan_entitlement',
            'subscription_plan_upgrade',
        ];
    }

    public static function assessment(): array {
        return [
            'admin/manage.php' => 'Legacy configuration controller mixes scope and plan writes with rendering.',
            'lib/' => 'Procedural storage and translation helpers remain coupled to historical subscription tables.',
            'tabs/' => 'Historical tab controllers execute reads, writes and rendering in the same request layer.',
            'boundary' => 'D1-D3 inventories this architecture but does not migrate or delete it.',
        ];
    }
}
