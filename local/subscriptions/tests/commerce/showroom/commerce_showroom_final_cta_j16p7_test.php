<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p7_test extends \advanced_testcase {
    public function test_mobile_final_cta_supersedes_dynamic_sticky_with_inline_flow(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $js = file_get_contents($root . 'amd/src/showroom.js');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString('const observeFinalCtaState = () => {', $js);
        self::assertStringContainsString("'commerce-showroom-final-cta-active'", $js);
        self::assertStringNotContainsString('const observeMobileFinalCtaSticky = () => {', $js);

        self::assertStringContainsString('data-showroom-final-inline-mobile-cta', $template);
        self::assertStringContainsString(
            'html.commerce-showroom-final-cta-active',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-final__inline-mobile-cta',
            $css
        );
        self::assertStringNotContainsString(
            '--showroom-final-mobile-sticky-bottom: 10.5rem;',
            $css
        );
    }
}
