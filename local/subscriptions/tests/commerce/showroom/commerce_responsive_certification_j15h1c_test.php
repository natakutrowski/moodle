<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_responsive_certification_j15h1c_test extends \advanced_testcase {
    public function test_remaining_mobile_certification_rules_are_present(): void {
        global $CFG;
        $showroom = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $styles = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        self::assertStringContainsString('clip-path: none', $showroom);
        self::assertStringContainsString('page-local-subscriptions-checkout', $styles);
        self::assertStringContainsString('-webkit-optimize-contrast', $showroom);
    }
}
