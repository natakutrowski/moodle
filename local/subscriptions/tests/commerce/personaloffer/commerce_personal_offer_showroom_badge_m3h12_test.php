<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_showroom_badge_m3h12_test extends \advanced_testcase {
    public function test_showroom_resolver_exposes_personal_offer_badge_only_with_personal_pricing(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );

        $this->assertStringContainsString("\$offer['ispersonaloffer'] = true", $source);
        $this->assertStringContainsString("\$offer['personalofferbadge'] = get_string(", $source);
        $this->assertStringContainsString('commerce_personal_offer_checkout_badge', $source);
    }

    public function test_showroom_offer_template_reuses_checkout_personal_offer_badge_design(): void {
        global $CFG;

        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );

        $this->assertStringContainsString('{{#ispersonaloffer}}', $template);
        $this->assertStringContainsString('commerce-personal-offer-badge', $template);
        $this->assertStringContainsString('{{personalofferbadge}}', $template);
        $this->assertStringContainsString('commerce-personal-offer-badge__star--left', $template);
        $this->assertStringContainsString('commerce-personal-offer-badge__star--right', $template);
    }
}
