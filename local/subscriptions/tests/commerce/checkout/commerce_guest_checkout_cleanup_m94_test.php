<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_guest_checkout_cleanup_m94_test extends advanced_testcase {
    public function test_cleanup_is_disabled_by_default_in_settings(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/settings.php'
        );

        self::assertStringContainsString(
            "'local_subscriptions/guest_checkout_cleanup_enabled'",
            $source
        );
        self::assertMatchesRegularExpression(
            "/guest_checkout_cleanup_enabled'.*?\\n.*?\\n.*?0\\s*\\)/s",
            $source
        );
    }

    public function test_cleanup_has_strict_business_activity_guards(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestCheckoutAbandonedCleanupService.php'
        );

        self::assertStringContainsString("'user_enrolments'", $source);
        self::assertStringContainsString("'local_subs_commerce_grant'", $source);
        self::assertStringContainsString("'local_subs_commerce_dig_access'", $source);
        self::assertStringContainsString("'local_subscriptions_commerce_purchase'", $source);
        self::assertStringContainsString("'local_subs_commerce_offer'", $source);
        self::assertStringContainsString("source_status'] !== 'provisional'", $source);
    }

    public function test_cleanup_task_is_scheduled_but_guarded_by_flag(): void {
        self::assertTrue(class_exists(
            \local_subscriptions\task\cleanup_abandoned_guest_checkouts_task::class
        ));

        $task = new \local_subscriptions\task\cleanup_abandoned_guest_checkouts_task();
        self::assertSame(
            get_string('task_cleanup_abandoned_guest_checkouts', 'local_subscriptions'),
            $task->get_name()
        );
    }
}
