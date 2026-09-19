<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResolver;
use local_subscriptions\commerce\payment\policy\CommercePaymentRecommendationProfileRegistry;

final class commerce_796h1321_payment_recommendation_domain_test extends \advanced_testcase {
    /**
     * @dataProvider global_currency_provider
     */
    public function test_eur_usd_gbp_share_global_card_profile(
        string $currency
    ): void {
        $order = (new CommercePaymentRecommendationProfileRegistry())
            ->order($currency, 'FR');

        self::assertSame(
            [
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
                CommercePaymentMethod::KLARNA,
            ],
            array_slice($order, 0, 6)
        );
    }

    public static function global_currency_provider(): array {
        return [
            'EUR' => ['EUR'],
            'USD' => ['USD'],
            'GBP' => ['GBP'],
        ];
    }

    public function test_rub_profile_prefers_reliable_card_then_local_methods(): void {
        $order = (new CommercePaymentRecommendationProfileRegistry())
            ->order('RUB', 'RU');

        self::assertSame(
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::SBP,
                CommercePaymentMethod::ALFA_PAY,
                CommercePaymentMethod::SBERPAY,
                CommercePaymentMethod::MIR_PAY,
                CommercePaymentMethod::PAYPAL,
            ],
            array_slice($order, 0, 6)
        );
    }

    public function test_unknown_currency_falls_back_to_catalogue_without_binary_branch(): void {
        $order = (new CommercePaymentRecommendationProfileRegistry())
            ->order('CHF', 'CH');

        self::assertNotEmpty($order);
        self::assertSame(
            count($order),
            count(array_unique($order))
        );
    }

    public function test_policy_never_invents_unavailable_methods(): void {
        $available = [
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::CARD,
                'RUB',
                ['alfa']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::PAYPAL,
                'RUB',
                ['paypal']
            ),
        ];

        $result = (new CommercePaymentPolicyResolver())
            ->resolve('RU', 'RUB', $available);

        self::assertSame(
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
            ],
            array_map(
                static fn($item): string => $item->get_method(),
                $result->get_ordered_methods()
            )
        );
    }

    public function test_policy_reorders_only_methods_that_are_really_available(): void {
        $available = [
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::PAYPAL,
                'RUB',
                ['paypal']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::ALFA_PAY,
                'RUB',
                ['alfa']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::SBP,
                'RUB',
                ['alfa']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::CARD,
                'RUB',
                ['alfa']
            ),
        ];

        $result = (new CommercePaymentPolicyResolver())
            ->resolve('RU', 'RUB', $available);

        self::assertSame(
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::SBP,
                CommercePaymentMethod::ALFA_PAY,
                CommercePaymentMethod::PAYPAL,
            ],
            array_map(
                static fn($item): string => $item->get_method(),
                $result->get_ordered_methods()
            )
        );
    }

    public function test_explicit_country_does_not_turn_profile_into_rub_else_global_logic(): void {
        $registry = new CommercePaymentRecommendationProfileRegistry();

        self::assertSame(
            array_slice($registry->order('USD', 'RU'), 0, 6),
            array_slice($registry->order('USD', 'US'), 0, 6)
        );
        self::assertNotSame(
            array_slice($registry->order('RUB', 'RU'), 0, 6),
            array_slice($registry->order('USD', 'RU'), 0, 6)
        );
    }

    public function test_policy_source_has_no_old_ru_by_branch(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/policy/'
            . 'CommercePaymentPolicyResolver.php'
        );

        self::assertStringNotContainsString(
            'REGIONAL_RELIABILITY_COUNTRIES',
            $source
        );
        self::assertStringContainsString(
            'CommercePaymentRecommendationProfileRegistry',
            $source
        );
    }
}
