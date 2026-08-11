<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p2_test extends \advanced_testcase {
    public function test_final_cta_and_sticky_variant_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');
        $js = file_get_contents($root . 'amd/src/showroom.js');

        self::assertStringContainsString('data-showroom-final-cta', $template);
        self::assertStringContainsString('data-showroom-desktop-expedition', $template);
        self::assertStringContainsString('commerce-showroom-final__subtitle', $template);
        self::assertStringContainsString('commerce-showroom-desktop-sticky__expedition-glass', $template);

        self::assertStringContainsString(
            '/* J16P3 — Final CTA polish, split desktop sticky and legal footer. */',
            $css
        );
        self::assertStringContainsString('.commerce-showroom-desktop-sticky__layout', $css);

        self::assertStringContainsString(
            "const SELECTOR_FINAL_CTA = '[data-showroom-final-cta]';",
            $js
        );
        self::assertStringContainsString('const observeFinalCtaState = () => {', $js);
        self::assertStringContainsString('const observeDesktopSticky = () => {', $js);
    }
}
