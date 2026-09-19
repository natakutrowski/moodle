<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e4_payment_policy_test extends \advanced_testcase {

    public function test_rub_profile_recommends_card_and_preserves_other_available_methods(): void {
        $available = [
            new \local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability('apple_pay', 'RUB', ['stripe']),
            new \local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability('card', 'RUB', ['alfa']),
            new \local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability('paypal', 'RUB', ['paypal']),
        ];
        $policy = (new \local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResolver())->resolve('RU', 'RUB', $available);
        self::assertSame('card', $policy->get_recommended_method());
        self::assertSame('regional_card_recommended', $policy->get_advice_key());
        self::assertSame(
            ['card', 'paypal', 'apple_pay'],
            array_map(static fn($item): string => $item->get_method(), $policy->get_ordered_methods())
        );
    }


    public function test_eur_global_profile_prefers_available_express_wallet_before_card(): void {
        $available = [
            new \local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability('card', 'EUR', ['stripe']),
            new \local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability('apple_pay', 'EUR', ['stripe']),
        ];
        $policy = (new \local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResolver())->resolve('FR', 'EUR', $available);
        self::assertSame('apple_pay', $policy->get_recommended_method());
        self::assertNull($policy->get_advice_key());
    }

}
