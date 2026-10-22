<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_trial_banner_completion_test
        extends \advanced_testcase {

    public function test_banner_uses_native_conversion_offer_independently_from_free_trial_access(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'local_campus_trial_conversion_offer(',
            $source
        );
        $this->assertStringContainsString(
            'if ($trialoffer === null)',
            $source
        );
        $this->assertStringNotContainsString(
            'user_has_trial_access_remaining',
            $source
        );
    }

    public function test_native_paid_role_cleans_trial_when_last_role_is_removed(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/fulfillment/'
            . 'native/course/service/MoodleCourseRoleService.php'
        );

        $this->assertStringContainsString(
            'cleanup_trial_subscription_if_unused',
            $source
        );
        $this->assertStringContainsString(
            "\$roleshortname !== 'trialstudent'",
            $source
        );
    }
}
