<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_guest_checkout_crm_m93_test extends advanced_testcase {
    public function test_crm_queue_and_user360_wiring_exist(): void {
        global $CFG;

        self::assertFileExists(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php'
        );
        self::assertFileExists(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'
        );

        $factory = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );
        self::assertStringContainsString('User360GuestCheckoutRecoveryRenderer', $factory);
        self::assertStringContainsString('ITEM_GUEST_CHECKOUT_RECOVERY', $factory);
    }

    public function test_crm_actions_are_post_sesskey_protected(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'
        );

        self::assertStringContainsString('require_sesskey()', $source);
        self::assertStringContainsString('MANAGE_CRM_ADMIN_TOOLS', $source);
        self::assertStringContainsString('select_resume_purchase', $source);
        self::assertStringContainsString('reconcile_payment', $source);
    }

    public function test_navigation_has_unfinished_checkout_entry(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/commerce/rendering/CommerceSalesNavigationRenderer.php'
        );

                self::assertStringContainsString('unfinished-checkouts/index.php', $source);
    }
}
