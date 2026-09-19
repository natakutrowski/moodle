<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\currency\Currency;

/**
 * H13.1 — authoritative, currency-agnostic Commerce selection policy.
 *
 * This service deliberately knows nothing about EUR-vs-RUB markets or payment
 * providers. It only applies precedence to valid candidates constrained by the
 * currencies that the current commercial surface can actually sell.
 */
final class CommerceCurrencySelectionService {
    public function __construct(
        private readonly CommerceCurrencyRegistry $registry = new CommerceCurrencyRegistry()
    ) {
    }

    public function resolve(
        CommerceCurrencySelectionContext $context
    ): CommerceCurrencySelectionResult {
        $available =
            $this->normalize_available(
                $context->get_available()
            );

        $candidates = [
            [
                CommerceCurrencySelectionSource::EXPLICIT,
                $context->get_explicit(),
            ],
            [
                CommerceCurrencySelectionSource::ACTIVE_CART,
                $context->get_active_cart(),
            ],
            [
                CommerceCurrencySelectionSource::ACTIVE_GUEST_CHECKOUT,
                $context->get_active_guest_checkout(),
            ],
            [
                CommerceCurrencySelectionSource::USER_PREFERENCE,
                $context->get_user_preference(),
            ],
            [
                CommerceCurrencySelectionSource::SESSION_PREFERENCE,
                $context->get_session_preference(),
            ],
            [
                CommerceCurrencySelectionSource::MARKET_DEFAULT,
                $context->get_market_default(),
            ],
            [
                CommerceCurrencySelectionSource::COMMERCE_DEFAULT,
                $context->get_commerce_default(),
            ],
        ];

        foreach ($candidates as [$source, $candidate]) {
            $candidate =
                Currency::sanitize(
                    (string)$candidate
                );

            if (
                $candidate !== ''
                && in_array(
                    $candidate,
                    $available,
                    true
                )
            ) {
                return new CommerceCurrencySelectionResult(
                    $candidate,
                    $source
                );
            }
        }

        // A commercial surface with available currencies must always resolve
        // deterministically, even when none of the preference candidates match.
        return new CommerceCurrencySelectionResult(
            $available[0],
            CommerceCurrencySelectionSource::COMMERCE_DEFAULT
        );
    }

    /**
     * @param string[] $available
     * @return string[]
     */
    private function normalize_available(array $available): array {
        $enabled =
            $this->registry->enabled();

        $available =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(mixed $value): string =>
                                Currency::sanitize(
                                    (string)$value
                                ),
                            $available
                        )
                    )
                )
            );

        $available =
            array_values(
                array_filter(
                    $available,
                    static fn(string $currency): bool =>
                        in_array(
                            $currency,
                            $enabled,
                            true
                        )
                )
            );

        if ($available === []) {
            $available = $enabled;
        }

        if ($available === []) {
            throw new \coding_exception(
                'Commerce has no enabled currency available for selection.'
            );
        }

        return $available;
    }
}
