<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class customer_navigation_cleanup_test extends \advanced_testcase {
    public function test_customer_navigation_targets_public_url_factory_routes(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        $this->assertStringContainsString(
            'UrlFactory::my_courses()',
            $source
        );
        $this->assertStringContainsString(
            'UrlFactory::my_digital_products()',
            $source
        );
        $this->assertStringContainsString(
            'UrlFactory::my_profile(',
            $source
        );
        $this->assertStringNotContainsString(
            '/local/campus/mycourses.php',
            $source
        );
        $this->assertStringNotContainsString(
            '/local/subscriptions/my_digital_products.php',
            $source
        );
    }

    public function test_trial_conversion_links_use_storefront_without_subscription_modal(): void {
        global $CFG;

        $files = [
            '/local/campus/lib.php',
            '/local/campus/course.php',
            '/local/campus/trial_check.php',
            '/local/campus/trial_gate.php',
            '/local/campus/classes/hooks/output/callbacks.php',
        ];

        foreach ($files as $relative) {
            $source = file_get_contents($CFG->dirroot . $relative);

            $this->assertStringNotContainsString(
                '/local/subscriptions/subscribe.php',
                $source,
                $relative
            );
            $this->assertStringNotContainsString(
                'data-subs-modal',
                $source,
                $relative
            );
        }
    }
}
