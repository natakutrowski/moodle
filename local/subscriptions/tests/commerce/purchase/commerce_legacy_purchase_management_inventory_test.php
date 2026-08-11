<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\purchase\audit\CommerceLegacyPurchaseManagementInventory;

final class commerce_legacy_purchase_management_inventory_test extends \advanced_testcase {
    public function test_legacy_plan_and_scope_architecture_is_explicitly_inventoried(): void {
        $files = CommerceLegacyPurchaseManagementInventory::files();
        $this->assertContains('admin/manage.php', $files);
        $this->assertContains('lib/plans_lib.php', $files);
        $this->assertContains('lib/scopes_lib.php', $files);
        $this->assertContains('tabs/plans.php', $files);
        $this->assertContains('tabs/scopes.php', $files);
    }
}
