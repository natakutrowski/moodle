<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B11 cart pricing harmonisation contract. */
final class commerce_cart_pricing_harmonisation_j67b11_test
        extends \advanced_testcase {

    public function test_cart_uses_one_compact_price_partial(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/page.mustache'
        );
        $partial = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/price.mustache'
        );

        $this->assertIsString($page);
        $this->assertStringContainsString(
            '{{> local_subscriptions/cart/price }}',
            $page
        );
        $this->assertStringNotContainsString(
            'commerce-cart-line__trial-price',
            $page
        );
        $this->assertStringNotContainsString(
            'commerce-cart-line__catalogue-promo',
            $page
        );

        $this->assertIsString($partial);
        foreach ([
            'commerce-cart-price--standard',
            'commerce-cart-price--promotion',
            'commerce-cart-price--trial',
            'commerce-cart-price--upgrade',
            'cartpricefinalformatted',
            'cartpricecompareformatted',
        ] as $contract) {
            $this->assertStringContainsString(
                $contract,
                $partial
            );
        }
    }

    public function test_upgrade_badges_are_separate_commercial_benefits(): void {
        global $CFG;

        $partial = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/price.mustache'
        );

        $this->assertStringContainsString(
            '{{cartpriceupgradebadge}}',
            $partial
        );
        $this->assertStringContainsString(
            '{{cartpricetrialdiscountbadge}}',
            $partial
        );
        $this->assertStringContainsString(
            '{{cartpricepromotionbadge}}',
            $partial
        );
        $this->assertStringContainsString(
            '{{upgradepath}}',
            $partial
        );
    }

    public function test_upgrade_detail_shows_precise_initial_promotion(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/presentation/'
            . 'CommerceCartPresenter.php'
        );
        $details = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/'
            . 'upgrade_breakdown.mustache'
        );

        $this->assertStringContainsString(
            'commerce_pricing_initial_promotion_percent',
            $presenter
        );
        $this->assertStringContainsString(
            '{{commercialpromotionlabel}}',
            $details
        );
        $this->assertStringContainsString(
            '{{commercialcreditlabel}}',
            $details
        );
    }

    public function test_summary_separates_trial_and_upgrade_credit(): void {
        global $CFG;

        $summary = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/summary.mustache'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/presentation/'
            . 'CommerceCartPresenter.php'
        );

        $this->assertStringContainsString(
            'trialdiscounttotalformatted',
            $summary
        );
        $this->assertStringContainsString(
            'upgradecredittotalformatted',
            $summary
        );
        $this->assertStringContainsString(
            '$trialdiscounttotalminor',
            $presenter
        );
        $this->assertStringContainsString(
            '$upgradecredittotalminor',
            $presenter
        );
    }
}
