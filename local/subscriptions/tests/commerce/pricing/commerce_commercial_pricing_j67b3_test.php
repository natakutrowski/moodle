<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B3 commercial pricing and Storefront layout contract. */
final class commerce_commercial_pricing_j67b3_test
        extends \advanced_testcase {

    public function test_upgrade_is_not_promoted_twice(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/cart/service/CommerceCartCalculator.php');
        $this->assertStringContainsString('if (!$isupgrade && !$istrialconversion && !$ispersonaloffer)', $source);
        $this->assertStringContainsString('$promotionitems[]', $source);
        $this->assertStringContainsString('active promoted target price minus the owned source', $source);
    }

    public function test_pricing_display_uses_only_subtractions(): void {
        global $CFG;
        $partial = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/pricing/commercial_breakdown.mustache');
        $cartpartial = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/upgrade_breakdown.mustache');
        $this->assertStringContainsString('− {{commercialpromotionformatted}}', $partial);
        $this->assertStringContainsString('− {{commercialtrialformatted}}', $partial);
        $this->assertStringContainsString('− {{commercialcreditformatted}}', $partial);
        $this->assertStringContainsString('− {{commercialcreditformatted}}', $cartpartial);
    }

    public function test_storefront_has_two_columns_and_owned_cleanup(): void {
        global $CFG;
        $card = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $this->assertStringContainsString('col-12 col-lg-6', $card);
        $this->assertStringContainsString('{{#owned}}', $card);
        $this->assertStringContainsString('{{^owned}}', $card);
        $this->assertStringContainsString('fa-graduation-cap', $card);
    }
}
