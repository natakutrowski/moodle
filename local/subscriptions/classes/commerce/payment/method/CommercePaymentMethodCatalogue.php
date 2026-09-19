<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\method;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentMethodCatalogue {
    public static function all(): array {
        return [
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::CARD,
                CommercePaymentMethodDefinition::FAMILY_CARD,
                false,
                false,
                10,
                CommercePaymentMethodDefinition::PRESENTATION_PRIMARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                20,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                30,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::PAYPAL,
                CommercePaymentMethodDefinition::FAMILY_ACCOUNT,
                true,
                false,
                40,
                CommercePaymentMethodDefinition::PRESENTATION_PRIMARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::LINK,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                50,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::KLARNA,
                CommercePaymentMethodDefinition::FAMILY_BNPL,
                false,
                true,
                60,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::ALFA_PAY,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                70,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::SBP,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                80,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::SBERPAY,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                90,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
            new CommercePaymentMethodDefinition(
                CommercePaymentMethod::MIR_PAY,
                CommercePaymentMethodDefinition::FAMILY_WALLET,
                true,
                false,
                100,
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY
            ),
        ];
    }

    public static function get(string $method): CommercePaymentMethodDefinition {
        $method = CommercePaymentMethod::normalise($method);

        foreach (self::all() as $definition) {
            if ($definition->get_key() === $method) {
                return $definition;
            }
        }

        throw new \coding_exception(
            'Unknown Commerce payment method: ' . $method
        );
    }

    public static function keys(): array {
        return array_map(
            static fn(CommercePaymentMethodDefinition $definition): string =>
                $definition->get_key(),
            self::all()
        );
    }

    private function __construct() {
    }
}
