<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B9.1 Boutique pricing polish contract. */
final class commerce_storefront_polish_j67b91_test
        extends \advanced_testcase {

    public function test_owned_toggle_submits_catalogue_form_immediately(): void {
        global $CFG;
        $catalog = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/catalog.mustache');
        $this->assertStringContainsString(
            'data-storefront-owned-toggle',
            $catalog
        );
        $this->assertStringContainsString(
            'form.requestSubmit()',
            $catalog
        );
    }

    public function test_four_price_cases_follow_one_flat_structure(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $this->assertIsString($template);

        foreach ([
            'commerce-storefront-price--standard',
            'commerce-storefront-price--promotion',
            'commerce-storefront-price--trial',
            'commerce-storefront-price--upgrade',
            'commerce-storefront-price__label',
            'commerce-storefront-price__values',
        ] as $class) {
            $this->assertStringContainsString($class, $template);
        }

        $this->assertStringContainsString(
            'commerce_storefront_price_discovery',
            $template
        );
        $this->assertStringContainsString(
            'commerce_storefront_upgrade_offer_badge',
            $template
        );
        $this->assertStringContainsString(
            'commerce_storefront_upgrade_owned_explanation',
            $template
        );
    }

    public function test_upgrade_has_no_nested_visual_price_card(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $upgrade_start = strpos(
            $template,
            'commerce-storefront-price--upgrade'
        );
        $upgrade_end = strpos(
            $template,
            '{{/hasupgrade}}',
            $upgrade_start
        );
        $upgrade = substr(
            $template,
            $upgrade_start,
            $upgrade_end - $upgrade_start
        );

        $this->assertStringNotContainsString(
            'commerce-storefront-price commerce-storefront-price',
            $upgrade
        );
        $this->assertStringContainsString(
            'commerce-storefront-price__details',
            $upgrade
        );
    }
}
