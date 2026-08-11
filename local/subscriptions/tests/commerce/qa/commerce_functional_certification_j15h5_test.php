<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_subscriptions\commerce\qa;

use advanced_testcase;

/**
 * Static functional certification contracts for CampusFR Commerce 7.95 J15H.5.
 *
 * These tests intentionally validate high-risk integration contracts without
 * contacting payment providers or depending on browser automation.
 *
 * @coversNothing
 */
final class commerce_functional_certification_j15h5_test extends advanced_testcase {

    /**
     * Return a plugin-relative file path.
     */
    private function plugin_file(string $path): string {
        global $CFG;
        return $CFG->dirroot . '/local/subscriptions/' . ltrim($path, '/');
    }

    /**
     * Read a plugin file and fail with a useful message when it is missing.
     */
    private function read_plugin_file(string $path): string {
        $filename = $this->plugin_file($path);
        $this->assertFileExists($filename, 'Missing required Commerce file: ' . $path);
        $content = file_get_contents($filename);
        $this->assertIsString($content);
        return $content;
    }

    public function test_critical_public_routes_exist(): void {
        $routes = [
            'showroom.php',
            'digital_catalog.php',
            'cart.php',
            'commerce_checkout.php',
            'order_result.php',
            'order_details.php',
            'guest_account_activate.php',
        ];

        foreach ($routes as $route) {
            $this->assertFileExists($this->plugin_file($route), 'Missing critical route: ' . $route);
        }
    }

    public function test_storefront_product_card_has_balanced_responsive_cover_sections(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $coveropenings = substr_count($template, '{{#hascover}}')
            + substr_count($template, '{{^hascover}}');
        self::assertSame($coveropenings, substr_count($template, '{{/hascover}}'));
        self::assertSame(
            substr_count($template, '{{#coverresponsive}}'),
            substr_count($template, '{{/coverresponsive}}')
        );

    }

    public function test_guest_account_activation_anchor_contract_is_present(): void {
        $page = $this->read_plugin_file('guest_account_activate.php');
        $service = $this->read_plugin_file(
            'classes/commerce/checkout/guest/CommerceGuestAccountActivationService.php'
        );

        $this->assertStringContainsString('activation', $page);
        $this->assertStringContainsString("set_anchor('activation')", $service);
    }

    public function test_guest_account_dialog_is_shared_by_order_pages(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $result = file_get_contents($root . '/order_result.php');
        $details = file_get_contents($root . '/templates/order_details/page.mustache');
        self::assertStringContainsString('local_subscriptions/commerce/guest_account_dialog', $result);
        self::assertStringContainsString('local_subscriptions/commerce/guest_account_dialog', $details);


    }

    public function test_accessibility_and_performance_contracts_are_retained(): void {
        $showroom = $this->read_plugin_file('templates/showroom/third_group_verbs.mustache');
        $styles = $this->read_plugin_file('styles/showroom.css');

        $this->assertStringContainsString('prefers-reduced-motion', $styles);
        $this->assertStringContainsString('content-visibility', $styles);
        $this->assertStringContainsString('aria-', $showroom);
    }

    public function test_required_guest_activation_strings_exist_in_all_languages(): void {
        $identifiers = [
            'commerce_guest_activation_title_prefix',
            'commerce_guest_activation_quick_note',
            'commerce_guest_activation_email_label',
        ];

        foreach (['en', 'fr', 'ru'] as $language) {
            $lang = $this->read_plugin_file('lang/' . $language . '/local_subscriptions.php');
            foreach ($identifiers as $identifier) {
                $this->assertStringContainsString(
                    "\$string['" . $identifier . "']",
                    $lang,
                    'Missing language string ' . $identifier . ' in ' . $language
                );
            }
        }
    }
}
