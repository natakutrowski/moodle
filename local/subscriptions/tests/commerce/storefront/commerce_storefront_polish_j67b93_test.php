<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B9.3 final Boutique vertical-rhythm contract. */
final class commerce_storefront_polish_j67b93_test
        extends \advanced_testcase {

    public function test_upgrade_badges_are_inside_price_block(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $this->assertIsString($template);

        $upgradeblock = strpos(
            $template,
            'commerce-storefront-price--upgrade'
        );
        $badges = strpos(
            $template,
            'commerce-storefront-price__badges--upgrade'
        );
        $label = strpos(
            $template,
            '{{upgradepricelabelcard}}'
        );

        $this->assertIsInt($upgradeblock);
        $this->assertIsInt($badges);
        $this->assertIsInt($label);
        $this->assertGreaterThan($upgradeblock, $badges);
        $this->assertGreaterThan($badges, $label);
        $this->assertStringContainsString(
            '{{upgradefromlabel}}',
            $template
        );
        $this->assertStringContainsString(
            '{{upgradetolabel}}',
            $template
        );
    }

    public function test_standard_and_promotion_have_no_empty_badge_row(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $this->assertStringContainsString(
            'commerce-storefront-price__heading',
            $template
        );

        $standardstart = strpos(
            $template,
            'commerce-storefront-price{{#haspromotion}}'
        );
        $standardend = strpos(
            $template,
            '{{/istrialconversion}}',
            $standardstart
        );
        $standardblock = substr(
            $template,
            $standardstart,
            $standardend - $standardstart
        );

        $this->assertStringNotContainsString(
            'commerce-storefront-price__badges">',
            $standardblock
        );
    }

    public function test_styles_remove_fixed_price_card_heights(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertIsString($styles);
        $this->assertStringContainsString(
            'min-height: 0 !important',
            $styles
        );
        $this->assertStringContainsString(
            'grid-template-rows: none !important',
            $styles
        );
        $this->assertStringContainsString(
            '.commerce-storefront-price__details',
            $styles
        );
    }
}
