<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5762_notification_constant_test extends \advanced_testcase {
    public function test_guest_checkout_resume_uses_supported_notification_constant(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );

        self::assertStringNotContainsString(
            'notification::NOTIFY_NOTICE',
            $source
        );
        self::assertStringContainsString(
            'notification::NOTIFY_INFO',
            $source
        );
    }
}
