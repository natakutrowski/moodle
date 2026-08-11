<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Static regression checks for consolidated featured Bundle styling. */
final class commerce_showroom_bundle_emphasis_j15h1h2_test extends \advanced_testcase {
    public function test_consolidated_bundle_styles_disable_conflicting_animation(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('.commerce-showroom-offer--bundle.is-featured', $css);
        self::assertStringContainsString('animation: none !important', $css);
        self::assertStringContainsString('box-shadow:', $css);

    }

    public function test_old_conflicting_final_blocks_are_removed(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('.commerce-showroom-offer--bundle.is-featured::after', $css);
        self::assertStringContainsString('.commerce-showroom-offer--bundle.is-featured:hover', $css);


    }
}
