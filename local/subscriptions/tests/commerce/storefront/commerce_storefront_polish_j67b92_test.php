<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B9.2 promotion heading and Upgrade details simplification. */
final class commerce_storefront_polish_j67b92_test
        extends \advanced_testcase {

    public function test_promotion_label_and_badge_share_one_row(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'commerce-storefront-price__heading',
            $template
        );
        $this->assertStringContainsString(
            '{{promotionpricelabel}}',
            $template
        );
        $this->assertStringContainsString(
            '{{discountlabel}}',
            $template
        );
    }

    public function test_upgrade_summary_only_shows_details_label(): void {
        global $CFG;
        $partial = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/pricing/commercial_breakdown.mustache');
        $this->assertStringContainsString('commercialdetailslabel', $partial);
        $this->assertStringContainsString('commerce-commercial-price-breakdown__details-label', $partial);
        $this->assertStringNotContainsString('commercialofferlabel', $partial);
    }
}
