<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1316_personal_offer_currency_compatibility_test extends \advanced_testcase {
    public function test_offer_entry_uses_h13_market_and_personal_offer_selection(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/offer.php'
        );

        self::assertStringContainsString(
            'CommercePersonalOfferCurrencySelectionService',
            $source
        );
        self::assertStringContainsString(
            'CommerceMarketCountryResolver',
            $source
        );
        self::assertStringContainsString(
            'CommerceMarketCurrencyRecommendationService',
            $source
        );
        self::assertStringNotContainsString(
            "in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR'",
            $source
        );
        self::assertStringNotContainsString(
            'Region::detect_country()',
            $source
        );
    }

    public function test_personal_offer_selector_is_constrained_by_signed_offer_currencies(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/service/'
            . 'CommercePersonalOfferCurrencySelectionService.php'
        );

        self::assertStringContainsString(
            'validate_token(',
            $source
        );
        self::assertStringContainsString(
            'get_available_currencies(',
            $source
        );
        self::assertStringContainsString(
            'in_array(',
            $source
        );
        self::assertStringContainsString(
            'new CommerceCurrencySelectionService()',
            $source
        );
    }

    public function test_valid_explicit_offer_currency_wins(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/service/'
            . 'CommercePersonalOfferCurrencySelectionService.php'
        );

        $explicit = strpos(
            $source,
            "\$explicit !== ''"
        );
        $sourcepurchase = strpos(
            $source,
            '$offer->get_source_purchase_id()'
        );

        self::assertNotFalse($explicit);
        self::assertNotFalse($sourcepurchase);
        self::assertLessThan(
            $sourcepurchase,
            $explicit
        );
    }

    public function test_source_purchase_currency_keeps_pre_h13_priority_after_explicit_choice(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/service/'
            . 'CommercePersonalOfferCurrencySelectionService.php'
        );

        self::assertStringContainsString(
            'local_subscriptions_commerce_purchase',
            $source
        );
        self::assertStringContainsString(
            '$purchasecurrency',
            $source
        );
    }

    public function test_offer_currency_switch_still_reenters_signed_offer_boundary(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/offer_currency.php'
        );

        self::assertStringContainsString(
            "required_param('currency', PARAM_ALPHA)",
            $source
        );
        self::assertStringContainsString(
            'CommerceCurrencyRegistry',
            $source
        );
        self::assertStringContainsString(
            "new moodle_url('/local/subscriptions/offer.php'",
            $source
        );
        self::assertStringContainsString(
            "'destination' => 'checkout'",
            $source
        );
    }

    public function test_showroom_and_product_keep_offer_currency_scope(): void {
        global $CFG;

        foreach ([
            'showroom.php',
            'storefront_product.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/'
                . $relative
            );

            self::assertStringContainsString(
                'available_currencies(',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'array_intersect(',
                $source,
                $relative
            );
        }
    }
}
