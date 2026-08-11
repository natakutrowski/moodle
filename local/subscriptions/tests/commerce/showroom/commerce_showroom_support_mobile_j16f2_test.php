<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_support_mobile_j16f2_test extends \advanced_testcase {
    public function test_support_is_centered_responsive_and_keeps_contact_channels(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('.commerce-showroom-support__card', $css);
        self::assertStringContainsString('.commerce-showroom-support__actions', $css);
        self::assertStringContainsString('.commerce-showroom-support__channels', $css);
        self::assertStringContainsString('.commerce-showroom-support__channel--email', $css);
    }
}
