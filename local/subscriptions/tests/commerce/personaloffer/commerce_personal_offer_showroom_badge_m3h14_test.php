<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_showroom_badge_m3h14_test extends \advanced_testcase {
    public function test_public_showroom_loads_showroom_css_but_not_storefront_css(): void {
        global $CFG;

        $page = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');

        $this->assertStringContainsString('/local/subscriptions/styles/showroom.css', $page);
        $this->assertStringNotContainsString('/local/subscriptions/styles/storefront.css', $page);
    }

    public function test_showroom_badge_has_self_contained_gold_styles(): void {
        global $CFG;

        $css = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        $this->assertStringContainsString('.commerce-showroom-offer__personal-badge {', $css);
        $this->assertStringContainsString('linear-gradient(115deg, #fff8d7', $css);
        $this->assertStringContainsString('color: #6e4810 !important;', $css);
        $this->assertStringContainsString('color: #a66b07 !important;', $css);
        $this->assertStringContainsString('commerce-showroom-personal-offer-shine', $css);
    }
}
