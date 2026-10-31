<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\policy\CommercePaymentPolicyResolver;

defined('MOODLE_INTERNAL') || die();

/**
 * Regional payment advice is only useful if other methods are available.
 */
final class commerce_796e4_regional_card_advice_visibility_test extends \advanced_testcase {
    public function test_rub_card_only_does_not_display_regional_advice(): void {
        $policy = (new CommercePaymentPolicyResolver())->resolve('RU', 'RUB', [
            new CommercePaymentMethodAvailability('card', 'RUB', ['alfa']),
        ]);

        self::assertSame('card', $policy->get_recommended_method());
        self::assertCount(1, $policy->get_ordered_methods());
        self::assertNull($policy->get_advice_key());
        self::assertFalse($policy->has_advice());
    }

    public function test_rub_card_with_unavailable_alternative_has_no_advice(): void {
        $policy = (new CommercePaymentPolicyResolver())->resolve('BY', 'RUB', [
            new CommercePaymentMethodAvailability('card', 'RUB', ['alfa']),
            new CommercePaymentMethodAvailability('sbp', 'RUB', []),
        ]);

        self::assertCount(1, $policy->get_ordered_methods());
        self::assertNull($policy->get_advice_key());
    }

    public function test_rub_card_with_available_alternative_keeps_advice(): void {
        $policy = (new CommercePaymentPolicyResolver())->resolve('RU', 'RUB', [
            new CommercePaymentMethodAvailability('card', 'RUB', ['alfa']),
            new CommercePaymentMethodAvailability('sbp', 'RUB', ['alfa']),
        ]);

        self::assertCount(2, $policy->get_ordered_methods());
        self::assertSame('card', $policy->get_recommended_method());
        self::assertSame('regional_card_recommended', $policy->get_advice_key());
        self::assertTrue($policy->has_advice());
    }
}
