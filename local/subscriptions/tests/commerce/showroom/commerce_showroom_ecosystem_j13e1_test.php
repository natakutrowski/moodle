<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ecosystem_j13e1_test extends \advanced_testcase {
    public function test_showroom_uses_dedicated_fullwidth_layout(): void {
        global $CFG;
        $controller = file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($controller);
        self::assertIsString($css);
        self::assertStringContainsString("set_pagelayout('showroom')", $controller);
        self::assertStringContainsString('.commerce-showroom-offer--bundle', $css);
        self::assertStringContainsString('order: 2;', $css);
        self::assertStringContainsString(':focus-within', $css);
        self::assertStringContainsString('prefers-reduced-motion', $css);
    }
}
