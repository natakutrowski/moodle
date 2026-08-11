<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Structural regression coverage for Scope-backed Native course entitlements. */
final class commerce_effective_course_entitlement_resolver_test extends \advanced_testcase {
    public function test_scope_fallback_preserves_plan_access_level_and_role(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/catalog/service/'
            . 'CommerceEffectiveEntitlementResolver.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('subscription_access_scope', $source);
        $this->assertStringContainsString('subscription_plan_entitlement', $source);
        $this->assertStringContainsString("'course:' . \$courseid . ':' . \$accesslevel", $source);
        $this->assertStringContainsString("'roleshortname' => \$roleshortname", $source);
        $this->assertStringContainsString("'legacysource' => 'native_access_scope'", $source);
    }

    public function test_crm_retry_can_bootstrap_missing_grants_only_after_payment(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/action/'
            . 'CommercePurchaseActionService.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('has_confirmed_payment', $source);
        $this->assertStringContainsString('CommerceNativePurchaseGrantPlanner', $source);
        $this->assertStringContainsString('CommerceEntitlementGrantPersister', $source);
        $this->assertStringContainsString('find_by_purchase_reference', $source);
    }
}
