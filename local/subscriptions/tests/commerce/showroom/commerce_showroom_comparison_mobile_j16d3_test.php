<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_comparison_mobile_j16d3_test extends \advanced_testcase {
    public function test_mobile_comparison_uses_dynamic_offer_names_prices_and_cta(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertStringContainsString('{{mobilelabel}}', $template);
        self::assertStringContainsString('{{mobileprice}}', $template);
        self::assertStringContainsString('commerce-showroom-comparison-mobile__cta', $template);
    }

    public function test_mobile_navigation_uses_rail_width(): void {
        global $CFG;
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');

        self::assertStringContainsString('index * rail.clientWidth', $js);
        self::assertStringContainsString('SELECTOR_COMPARISON', $js);
    }
}
