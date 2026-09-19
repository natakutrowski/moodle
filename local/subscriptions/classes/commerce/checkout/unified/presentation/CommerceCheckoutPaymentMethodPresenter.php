<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified\presentation;

use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodDefinition;

defined('MOODLE_INTERNAL') || die();

/**
 * Public-safe customer-facing payment method presentation.
 *
 * PSP/provider identities are deliberately omitted.
 */
final class CommerceCheckoutPaymentMethodPresenter {
    /**
     * @param CommercePaymentMethodAvailability[] $available
     * @return array{
     *   methods:array<int,array<string,mixed>>,
     *   primaryactioncards:array<int,array<string,mixed>>,
     *   secondaryactioncards:array<int,array<string,mixed>>,
     *   primarymethods:array<int,array<string,mixed>>,
     *   quickmethods:array<int,array<string,mixed>>,
     *   othermethods:array<int,array<string,mixed>>,
     *   hasmethods:bool,
     *   hasprimaryactioncards:bool,
     *   hassecondaryactioncards:bool,
     *   hasquickmethods:bool,
     *   hasothermethods:bool,
     *   selectedmethod:string
     * }
     */
    public static function present(
        array $available,
        string $selectedmethod,
        ?string $recommendedmethod = null,
        ?string $advicekey = null
    ): array {
        $methods = [];

        foreach ($available as $index => $availability) {
            if (!$availability instanceof CommercePaymentMethodAvailability) {
                continue;
            }

            $method = $availability->get_method();
            $definition = CommercePaymentMethodCatalogue::get(
                $method
            );
            $presentation =
                $definition->get_presentation();
            $executionmode =
                CommerceCheckoutExecutionPolicy::mode_for_method(
                    $method
                );

            $methods[] = [
                'key' => $method,
                'executionmode' => $executionmode,
                'ishosted' =>
                    $executionmode
                        === CommerceCheckoutExecutionMode::PROVIDER_HOSTED,
                'isembedded' =>
                    $executionmode
                        === CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
                'label' => self::label($method),
                'description' => self::description($method),
                'iconclass' => self::icon_class($method),
                'iconurl' => self::icon_url($method),
                'hasiconurl' => self::icon_url($method) !== null,
                'iscard' => $method === CommercePaymentMethod::CARD,
                'ispaypal' => $method === CommercePaymentMethod::PAYPAL,
                'islink' => $method === CommercePaymentMethod::LINK,
                'isklarna' => $method === CommercePaymentMethod::KLARNA,
                'isalfapay' => $method === CommercePaymentMethod::ALFA_PAY,
                'issbp' => $method === CommercePaymentMethod::SBP,
                'issberpay' => $method === CommercePaymentMethod::SBERPAY,
                'ismirpay' => $method === CommercePaymentMethod::MIR_PAY,
                'presentation' => $presentation,
                'isprimaryactioncard' =>
                    $presentation ===
                        CommercePaymentMethodDefinition::PRESENTATION_PRIMARY,
                'issecondaryactioncard' =>
                    $presentation ===
                        CommercePaymentMethodDefinition::PRESENTATION_SECONDARY,
                // Legacy H12.2 compatibility only. H12.7.2 will remove the
                // quick-vs-radio rendering split in favour of action cards.
                'isquickaction' => in_array(
                    $method,
                    [CommercePaymentMethod::ALFA_PAY, CommercePaymentMethod::SBP],
                    true
                ),
                'requiresclienteligibility' => false,
                'selected' => $method === $selectedmethod,
                'recommended' => $recommendedmethod !== null
                    ? $method === $recommendedmethod
                    : $index === 0,
            ];
        }

        // H12.7.1 presentation hierarchy. This is now the canonical
        // customer-facing layout model; it is provider-independent and does
        // not imply anything about the execution rail behind the card.
        $primaryactioncards = array_values(
            array_filter(
                $methods,
                static fn(array $item): bool =>
                    !empty($item['isprimaryactioncard'])
            )
        );
        $secondaryactioncards = array_values(
            array_filter(
                $methods,
                static fn(array $item): bool =>
                    !empty($item['issecondaryactioncard'])
            )
        );

        // H12.7.2: page.mustache now consumes action-card buckets; legacy buckets remain compatibility-only.
        // H12.2 zero-friction hierarchy:
        // Alfa Pay and SBP are action-oriented methods: expose them as direct
        // payment buttons instead of radio-card + submit-button pairs. The
        // remaining executable methods stay visible as first-class choices.
        $quick = array_values(
            array_filter(
                $methods,
                static fn(array $item): bool => !empty($item['isquickaction'])
            )
        );
        $primary = array_values(
            array_filter(
                $methods,
                static fn(array $item): bool => empty($item['isquickaction'])
            )
        );
        $others = [];

        $advice = '';
        if ($advicekey !== null && trim($advicekey) !== '') {
            $stringkey = 'commerce_payment_policy_' . $advicekey;
            if (
                get_string_manager()->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )
            ) {
                $advice = get_string(
                    $stringkey,
                    'local_subscriptions'
                );
            }
        }

        return [
            'methods' => $methods,
            'primaryactioncards' => $primaryactioncards,
            'secondaryactioncards' => $secondaryactioncards,
            'hasprimaryactioncards' => $primaryactioncards !== [],
            'hassecondaryactioncards' => $secondaryactioncards !== [],
            'primarymethods' => $primary,
            'quickmethods' => $quick,
            'othermethods' => $others,
            'hasmethods' => $methods !== [],
            'hasquickmethods' => $quick !== [],
            'hasothermethods' => $others !== [],
            'selectedmethod' => $selectedmethod,
            'haspaymentpolicyadvice' => $advice !== '',
            'paymentpolicyadvice' => $advice,
        ];
    }

    private static function label(string $method): string {
        return get_string(
            'commerce_payment_method_' . $method,
            'local_subscriptions'
        );
    }

    private static function description(string $method): string {
        return get_string(
            'commerce_payment_method_' . $method . '_desc',
            'local_subscriptions'
        );
    }

    private static function icon_class(string $method): string {
        return match ($method) {
            CommercePaymentMethod::CARD => 'fa-regular fa-credit-card',
            CommercePaymentMethod::APPLE_PAY => 'fa-brands fa-apple',
            CommercePaymentMethod::GOOGLE_PAY => 'fa-brands fa-google',
            CommercePaymentMethod::PAYPAL => '',
            CommercePaymentMethod::LINK => 'fa-solid fa-link',
            CommercePaymentMethod::KLARNA => 'fa-solid fa-money-bill-wave',
            default => 'fa-solid fa-wallet',
        };
    }

    private static function icon_url(string $method): ?string {
        $file = match ($method) {
            CommercePaymentMethod::PAYPAL => 'paypal.svg',
            CommercePaymentMethod::LINK => 'link.svg',
            CommercePaymentMethod::KLARNA => 'klarna.svg',
            CommercePaymentMethod::ALFA_PAY => 'alfapay.svg',
            CommercePaymentMethod::SBP => 'sbp.svg',
            CommercePaymentMethod::SBERPAY => 'sberpay.svg',
            CommercePaymentMethod::MIR_PAY => 'mirpay.svg',
            default => null,
        };

        if ($file === null) {
            return null;
        }

        return (
            new \moodle_url(
                '/local/subscriptions/pix/providers/' . $file
            )
        )->out(false);
    }
}
