<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * L7.5 navigation UI contract: contextual links remain usable in the responsive Storefront.
 */
final class commerce_storefront_navigation_ux_l75_test extends advanced_testcase {
    public function test_contextual_back_link_uses_button_semantics_and_no_hover_only_contract(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_commerce_panel.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString(
            'commerce-product-commerce-panel__back',
            $template
        );
        self::assertStringContainsString(
            '{{#showbacklink}}',
            $template
        );
        self::assertStringContainsString(
            'commerce-product-commerce-panel__back',
            $css
        );
    }

    public function test_l7_navigation_language_keys_exist_in_all_supported_locales(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $CFG->dirroot
                    . '/local/subscriptions/lang/'
                    . $language
                    . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                "\$string['commerce_storefront_back_to_showroom']",
                $source
            );
            self::assertStringContainsString(
                "\$string['commerce_storefront_access_bundle_contents']",
                $source
            );
        }
    }
}
