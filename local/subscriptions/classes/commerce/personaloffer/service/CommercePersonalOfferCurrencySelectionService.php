<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext;
use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService;
use local_subscriptions\currency\Currency;

/**
 * H13.1.6 — Personal Offer currency selection on top of the H13 domain.
 *
 * The signed offer remains authoritative for the currencies the customer may
 * use. H13 only orders candidates inside that allowed set.
 */
final class CommercePersonalOfferCurrencySelectionService {
    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;

        return new self(
            $db ?? $DB
        );
    }

    public function resolve(
        string $token,
        string $explicit = '',
        string $userpreference = '',
        string $sessionpreference = '',
        string $marketdefault = 'EUR'
    ): string {
        $validation =
            CommercePersonalOfferFactory::create(
                $this->db
            )->validate_token(
                $token
            );

        if (
            !$validation->is_valid()
            || $validation->get_offer() === null
        ) {
            throw new \moodle_exception(
                'commerce_personal_offer_link_unavailable',
                'local_subscriptions'
            );
        }

        $offer =
            $validation->get_offer();
        $checkout =
            CommercePersonalOfferCheckoutService::create(
                $this->db
            );

        $available =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(array $candidate): string =>
                                Currency::sanitize(
                                    (string)(
                                        $candidate['currency']
                                        ?? ''
                                    )
                                ),
                            $checkout->get_available_currencies(
                                $offer
                            )
                        )
                    )
                )
            );

        if ($available === []) {
            throw new \moodle_exception(
                'commerce_personal_offer_currency_unavailable',
                'local_subscriptions'
            );
        }

        $explicit =
            Currency::sanitize(
                $explicit
            );

        if (
            $explicit !== ''
            && in_array(
                $explicit,
                $available,
                true
            )
        ) {
            return $explicit;
        }

        // Preserve the pre-H13 Personal Offer invariant: an offer derived from
        // an existing purchase prefers the source purchase currency whenever
        // that currency is still valid for the target offer.
        if ($offer->get_source_purchase_id()) {
            $purchasecurrency =
                Currency::sanitize(
                    (string)$this->db->get_field(
                        'local_subscriptions_commerce_purchase',
                        'currency',
                        [
                            'id' =>
                                $offer->get_source_purchase_id(),
                        ],
                        IGNORE_MISSING
                    )
                );

            if (
                $purchasecurrency !== ''
                && in_array(
                    $purchasecurrency,
                    $available,
                    true
                )
            ) {
                return $purchasecurrency;
            }
        }

        if (count($available) === 1) {
            return $available[0];
        }

        return (
            new CommerceCurrencySelectionService()
        )->resolve(
            new CommerceCurrencySelectionContext(
                available: $available,
                userpreference:
                    Currency::sanitize(
                        $userpreference
                    ),
                sessionpreference:
                    Currency::sanitize(
                        $sessionpreference
                    ),
                marketdefault:
                    Currency::sanitize(
                        $marketdefault
                    ),
                commercedefault: 'EUR'
            )
        )->get_currency();
    }
}
