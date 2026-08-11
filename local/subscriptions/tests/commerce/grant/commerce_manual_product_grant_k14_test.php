<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_manual_product_grant_k14_test extends advanced_testcase {

    public function test_manual_grant_reuses_native_stack_without_fake_purchase(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/grant/CommerceManualProductGrantService.php'
        );

        $this->assertStringContainsString('CommerceBundleExpansionService', $source);
        $this->assertStringContainsString('CommerceEffectiveEntitlementResolver', $source);
        $this->assertStringContainsString('CommerceEntitlementGrantPersister', $source);
        $this->assertStringContainsString('CommerceCourseAccessFulfillmentHandler', $source);
        $this->assertStringContainsString('CommerceDigitalDownloadFulfillmentHandler', $source);
        $this->assertStringContainsString("public const SOURCE = 'crm_manual_grant';", $source);
        $this->assertStringNotContainsString(
            "insert_record('local_subscriptions_commerce_purchase'",
            $source
        );
    }

    public function test_admin_add_page_exposes_legacy_and_native_modes(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents($root . '/admin/subscriptions/add.php');
        $renderer = (string)file_get_contents($root . '/renderer/user_subs_renderer.php');

        $this->assertStringContainsString('CommerceManualProductGrantService', $page);
        $this->assertStringContainsString("'native_product_id'", $page);
        $this->assertStringContainsString("'grant_mode'", $renderer);
        $this->assertStringContainsString("'native_product_id'", $renderer);
    }
}
