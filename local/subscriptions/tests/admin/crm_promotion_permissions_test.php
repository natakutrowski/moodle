<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\CrmRoleTemplates;

/** Regression tests: promotion viewing and XP awards are independent capabilities. */
final class crm_promotion_permissions_test extends \advanced_testcase {
    public function test_default_support_can_view_but_cannot_manage_or_edit_xp(): void {
        $support = CrmRoleTemplates::capabilities('campus_crm_support');
        self::assertContains(Capabilities::VIEW_PROMOTIONS, $support);
        self::assertNotContains(Capabilities::MANAGE_PROMOTIONS, $support);
        self::assertNotContains(Capabilities::MANAGE_PROMOTION_XP, $support);
        self::assertNotContains(Capabilities::MANAGE_CONFIGURATION, $support);
    }

    public function test_technical_can_manage_and_edit_xp_but_deletion_remains_siteadmin_only(): void {
        $technical = CrmRoleTemplates::capabilities('campus_crm_technical');
        self::assertContains(Capabilities::VIEW_PROMOTIONS, $technical);
        self::assertContains(Capabilities::MANAGE_PROMOTIONS, $technical);
        self::assertContains(Capabilities::MANAGE_PROMOTION_XP, $technical);
        // Deletion is an is_siteadmin() check, not a delegable CRM capability.
        self::assertNotContains('local/subscriptions:delete_promotions', $technical);
    }

    public function test_support_can_be_given_xp_right_without_promotion_editing(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $ctx = \context_system::instance();
        $roleid = create_role('Promotion support test', 'cr_promotion_support_test', '', '');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability(Capabilities::VIEW_PROMOTIONS, CAP_ALLOW, $roleid, $ctx->id);
        role_assign($roleid, $user->id, $ctx->id);
        $this->setUser($user);
        self::assertTrue(has_capability(Capabilities::VIEW_PROMOTIONS, $ctx));
        self::assertFalse(has_capability(Capabilities::MANAGE_PROMOTIONS, $ctx));
        self::assertFalse(has_capability(Capabilities::MANAGE_PROMOTION_XP, $ctx));
        assign_capability(Capabilities::MANAGE_PROMOTION_XP, CAP_ALLOW, $roleid, $ctx->id, true);
        \accesslib_clear_all_caches(true);
        self::assertTrue(has_capability(Capabilities::MANAGE_PROMOTION_XP, $ctx));
        self::assertFalse(has_capability(Capabilities::MANAGE_PROMOTIONS, $ctx));
    }

    public function test_promotion_xp_right_is_independent_and_has_translations(): void {
        foreach (['fr', 'ru', 'en'] as $language) {
            foreach (['view_promotions', 'manage_promotions', 'manage_promotion_xp'] as $right) {
                $string = get_string_manager()->get_string('subscriptions:' . $right,
                    'local_subscriptions', null, $language);
                self::assertNotEmpty($string);
                self::assertStringNotContainsString('[[', $string);
            }
        }
    }
}
