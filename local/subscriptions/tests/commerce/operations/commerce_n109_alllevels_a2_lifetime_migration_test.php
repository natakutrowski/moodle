<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n109_alllevels_a2_lifetime_migration_test extends \advanced_testcase {
    private function script(): string {
        $path = __DIR__ . '/../../../cli/commerce/operations/migrate_alllevels_a2_lifetime.php';
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_migration_is_dry_run_by_default_and_requires_explicit_confirmation(): void {
        $script = $this->script();

        self::assertStringContainsString("'execute' => false", $script);
        self::assertStringContainsString(
            "const N109_CONFIRM_TOKEN = 'ALLLEVELS-A2-LIFETIME';",
            $script
        );
        self::assertStringContainsString('--execute --confirm=', $script);
    }

    public function test_migration_targets_expected_scope_course_product_and_durations(): void {
        $script = $this->script();

        self::assertStringContainsString('const N109_SCOPE_ID = 13;', $script);
        self::assertStringContainsString('const N109_COURSE_ID = 13;', $script);
        self::assertStringContainsString("const N109_TARGET_SKU = 'SUB.PLAN.30';", $script);
        self::assertStringContainsString(
            "\$eligibledurations = ['1year', '3years', 'lifetime'];",
            $script
        );
    }

    public function test_migration_is_silent_and_uses_manual_native_grant_service(): void {
        $script = $this->script();

        self::assertStringContainsString('CommerceManualProductGrantService', $script);
        self::assertStringNotContainsString('CommerceGrantAccessMailService::', $script);
        self::assertStringNotContainsString('new CommerceGrantAccessMailService', $script);
        self::assertStringNotContainsString('CommerceGrantCampaignMailService', $script);
        self::assertStringNotContainsString('mailer::dispatch', $script);
        self::assertStringNotContainsString('email_to_user(', $script);
        self::assertStringNotContainsString('queue_and_send(', $script);
    }

    public function test_target_product_is_validated_as_lifetime_course_access(): void {
        $script = $this->script();

        self::assertStringContainsString('CommerceEffectiveEntitlementResolver', $script);
        self::assertStringContainsString('resolve_by_product_sku(N109_TARGET_SKU)', $script);
        self::assertStringContainsString(
            "\$definition->get_type() !== 'course_access'",
            $script
        );
        self::assertStringContainsString(
            "\$definition->is_lifetime()",
            $script
        );
        self::assertStringContainsString(
            "\$configuredcourseid === N109_COURSE_ID",
            $script
        );
        self::assertStringContainsString(
            "str_starts_with(\$resourcekey, 'course:' . N109_COURSE_ID . ':')",
            $script
        );
    }

    public function test_single_user_mode_filters_historical_population(): void {
        $script = $this->script();

        self::assertStringContainsString("'userid' => 0", $script);
        self::assertStringContainsString(
            "\$targetuserid = (int)(\$options['userid'] ?? 0);",
            $script
        );
        self::assertStringContainsString(
            "\$singleusermode = \$targetuserid > 0;",
            $script
        );
        self::assertStringContainsString(
            "AND us.userid = :targetuserid",
            $script
        );
    }

    public function test_single_user_execute_can_never_reach_global_scope_cleanup(): void {
        $script = $this->script();

        $singleexit = strpos(
            $script,
            'Single-user verification mode complete'
        );
        $scopeupdate = strpos(
            $script,
            "\$DB->update_record('subscription_access_scope', \$scope);"
        );
        $entitlementdelete = strpos(
            $script,
            "\$DB->delete_records(\n            'subscription_plan_entitlement'"
        );

        self::assertNotFalse($singleexit);
        self::assertNotFalse($scopeupdate);
        self::assertNotFalse($entitlementdelete);
        self::assertLessThan($scopeupdate, $singleexit);
        self::assertLessThan($entitlementdelete, $singleexit);

        self::assertStringContainsString(
            "AccessScope #13 modified:      NO",
            $script
        );
        self::assertStringContainsString(
            "Plan entitlements modified:    NO",
            $script
        );
    }

    public function test_inactive_target_product_is_only_temporarily_activated_and_always_restored(): void {
        $script = $this->script();

        self::assertStringContainsString(
            "\$originalproductstatus = (string)\$product->status;",
            $script
        );
        self::assertStringContainsString(
            "\$temporarilyactivated = false;",
            $script
        );
        self::assertStringContainsString(
            "'local_subs_commerce_product',\n                'status',\n                'active'",
            $script
        );
        self::assertStringContainsString(
            '} finally {',
            $script
        );
        self::assertStringContainsString(
            "'status',\n                \$originalproductstatus",
            $script
        );
        self::assertStringContainsString(
            'Safety invariant failed: target product status was not restored',
            $script
        );

        $activate = strpos($script, "'status',\n                'active'");
        $grant = strpos($script, '$grantservice->grant(');
        $finally = strpos($script, '} finally {');
        self::assertNotFalse($activate);
        self::assertNotFalse($grant);
        self::assertNotFalse($finally);
        self::assertLessThan($grant, $activate);
        self::assertLessThan($finally, $grant);
    }

    public function test_global_cleanup_remains_after_successful_grants_only(): void {
        $script = $this->script();

        $grant = strpos($script, '$grantservice->grant(');
        $scopeupdate = strpos(
            $script,
            "\$DB->update_record('subscription_access_scope', \$scope);"
        );

        self::assertNotFalse($grant);
        self::assertNotFalse($scopeupdate);
        self::assertLessThan($scopeupdate, $grant);

        self::assertStringContainsString(
            "\$courseid !== N109_COURSE_ID",
            $script
        );
        self::assertStringContainsString(
            "'subscription_plan_entitlement'",
            $script
        );
    }
}
