<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_customer_course_images_j10e5_test extends \advanced_testcase {
    public function test_mon_campus_reuses_moodle_course_overview_image_resolver(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/hub/'
                . 'CommerceCustomerHubService.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/customer/hub.mustache'
        );

        self::assertIsString($service);
        self::assertIsString($template);
        self::assertStringContainsString(
            'local_campus\\mycourses\\MyCourseImageService',
            $service
        );
        self::assertStringContainsString(
            'get_course_overviewfiles()',
            $service
        );
        self::assertStringContainsString(
            'commerce-customer-hub__course-cover',
            $template
        );
        self::assertStringContainsString(
            '{{#hasimage}}',
            $template
        );
    }
}
