<?php
// This file is part of Moodle - http://moodle.org/

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Guards the first J15H mobile certification corrections.
 *
 * @package local_subscriptions
 */
final class commerce_responsive_certification_j15h1a_test extends \advanced_testcase {
    public function test_commerce_pages_use_the_chromeless_shell(): void {
        global $CFG;

        foreach (['digital_catalog.php', 'cart.php', 'checkout.php'] as $filename) {
            $source = file_get_contents($CFG->dirroot . '/local/subscriptions/' . $filename);
            self::assertIsString($source);
            self::assertStringContainsString("add_body_class('commerce-chromeless-page')", $source);
        }
    }

    public function test_mobile_css_contains_certified_offer_and_badge_overrides(): void {
        global $CFG;

        $showroom = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $storefront = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');

        self::assertIsString($showroom);
        self::assertIsString($storefront);
        self::assertStringContainsString('grid-auto-columns: calc(100vw - 2.5rem)', $showroom);
        self::assertStringContainsString('top: -.8rem', $showroom);
        self::assertStringContainsString('width: 2.85rem', $storefront);
    }

    public function test_order_result_resolves_catalogue_product_titles(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');

        self::assertIsString($source);
        self::assertStringContainsString('CommerceStorefrontRepository', $source);
        self::assertStringContainsString('find_by_sku', $source);
        self::assertStringContainsString('CommerceProductDisplayText::title($item->label)', $source);
    }
}
