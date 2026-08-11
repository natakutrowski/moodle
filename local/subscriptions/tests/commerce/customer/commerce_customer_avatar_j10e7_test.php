<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_avatar_j10e7_test extends \advanced_testcase {
    public function test_hub_requests_a_high_resolution_profile_picture(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/customer/hub/'
            . 'CommerceCustomerHubService.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('$picture->size = 200;', $source);
        $this->assertStringContainsString(
            <<<'PHP'
        'avatarurl' => $picture->get_url($page)->out(false)
        PHP,
            $source
        );
    }
}
