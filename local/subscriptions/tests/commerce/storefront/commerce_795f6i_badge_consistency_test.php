<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

final class commerce_795f6i_badge_consistency_test extends \advanced_testcase {
    public function test_product_page_keeps_only_type_badge_in_header(): void {
        global $CFG;
        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_templates/default.mustache'
        );

        self::assertStringContainsString('commerce-product-type-badge mb-3', $template);
        self::assertStringNotContainsString(
            '{{> local_subscriptions/storefront/product_badges }}',
            strstr($template, '<h1', true) ?: ''
        );
    }

    public function test_commerce_panel_uses_shared_formatted_badges(): void {
        global $CFG;
        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );

        self::assertStringContainsString('{{> local_subscriptions/storefront/product_badges }}', $template);
        self::assertStringNotContainsString('{{#badges}}<span class="badge rounded-pill', $template);
    }

    public function test_gustave_size_is_preserved_independently_of_badge_height(): void {
        global $CFG;
        $css = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');

        self::assertStringContainsString('height: 1.75rem;', $css);
        self::assertStringContainsString('width: 4.0rem;', $css);
        self::assertStringContainsString('height: 4.0rem;', $css);
    }
}
