<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_trial_banner_completion_test
        extends \advanced_testcase {

    public function test_banner_requires_remaining_trial_course_role(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        $this->assertStringContainsString(
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
