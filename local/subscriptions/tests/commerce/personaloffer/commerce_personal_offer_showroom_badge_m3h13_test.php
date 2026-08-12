<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_showroom_badge_m3h13_test extends \advanced_testcase {
    public function test_badge_is_rendered_inside_price_row_only_for_personal_offer(): void {
        global $CFG;

        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );

        $pricepos = strpos($template, 'commerce-showroom-offer__price-row');
        $badgepos = strpos($template, 'commerce-showroom-offer__personal-badge');
        $rolepos = strpos($template, 'commerce-showroom-offer__role');

        $this->assertNotFalse($pricepos);
        $this->assertNotFalse($badgepos);
        $this->assertNotFalse($rolepos);
        $this->assertGreaterThan($pricepos, $badgepos);
        $this->assertGreaterThan($rolepos, $pricepos);
        $this->assertStringContainsString('{{#ispersonaloffer}}', $template);
        $this->assertStringContainsString('{{personalofferbadge}}', $template);
    }

    public function test_showroom_has_its_own_self_contained_personal_offer_badge_rules(): void {
        global $CFG;

        $css = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        // M3H.14 deliberately keeps the public Showroom independent from storefront.css.
        $this->assertStringContainsString('.commerce-showroom-offer__personal-badge', $css);
        $this->assertStringContainsString('display: inline-flex', $css);
        $this->assertStringContainsString('border-radius: 999px', $css);
        $this->assertStringContainsString('white-space: nowrap', $css);
        $this->assertStringContainsString('#6e4810', $css);
        $this->assertStringContainsString('#a66b07', $css);
    }
}
