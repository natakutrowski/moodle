<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_xp_images_j10e61_test extends \advanced_testcase {
    public function test_hub_reuses_moodle_course_image_service_and_exposes_ranking(): void {
        global $CFG;

        $hub = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/hub/CommerceCustomerHubService.php'
        );
        $repository = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/crm/success/repositories/LevelUpXpRepository.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/customer/hub.mustache'
        );

        $this->assertIsString($hub);
        $this->assertStringContainsString(
            '\\local_campus\\mycourses\\MyCourseImageService',
            $hub
        );
        $this->assertStringContainsString(
            "'xprank' => \$xp['rank']",
            $hub
        );
        $this->assertIsString($repository);
        $this->assertStringContainsString("'leaderboard_rank'", $repository);
        $this->assertStringContainsString('get_absolute_rank', $repository);
        $this->assertIsString($template);
        $this->assertStringContainsString(
            'commerce_customer_hub_xp_ranking',
            $template
        );
        $this->assertStringContainsString('fa-graduation-cap', $template);
    }
}
