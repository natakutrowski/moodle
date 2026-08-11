<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_offer_inner_harmony_j16a2_test extends \advanced_testcase {
    public function test_featured_bundle_keeps_shared_card_contract(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('.commerce-showroom-offer--bundle.is-featured', $css);
        self::assertStringContainsString('height: calc(100% + 1.5rem);', $css);
        self::assertStringContainsString('transform: translateY(-16px) !important;', $css);
    }
}
