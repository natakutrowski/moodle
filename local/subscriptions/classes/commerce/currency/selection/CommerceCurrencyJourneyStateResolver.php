<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\currency\Currency;

/**
 * H13.1.4 — extracts already-engaged Commerce journey currency candidates.
 *
 * It is read-only: it never opens/creates/switches a cart and never mutates a
 * Guest Checkout. Selection policy remains in CommerceCurrencySelectionService.
 */
final class CommerceCurrencyJourneyStateResolver {
    private const CART_SESSION_PROPERTY =
        'local_subscriptions_commerce_carts';

    public function __construct(
        private readonly CommerceGuestCheckoutSessionRepository $guestsessions
    ) {
    }

    public static function create(): self {
        global $DB;

        return new self(
            new CommerceGuestCheckoutSessionRepository($DB)
        );
    }

    public function active_guest_checkout_currency(): string {
        global $SESSION;

        $token = trim((string)(
            $SESSION->local_subscriptions_guest_checkout_token
            ?? ''
        ));

        if ($token === '') {
            return '';
        }

        $session =
            $this->guestsessions->find_by_token(
                $token
            );

        if (
            $session === null
            || $session->is_expired()
        ) {
            return '';
        }

        return Currency::sanitize(
            $session->get_currency()
        );
    }

    /**
     * Returns the most recently modified non-empty cart for the current
     * Commerce identity, constrained to currencies offered by the surface.
     *
     * @param string[] $available
     */
    public function active_cart_currency(
        array $available
    ): string {
        global $SESSION, $USER;

        $available =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(mixed $currency): string =>
                                Currency::sanitize(
                                    (string)$currency
                                ),
                            $available
                        )
                    )
                )
            );

        if ($available === []) {
            return '';
        }

        $customerid = 0;

        if (
            isloggedin()
            && !isguestuser()
        ) {
            $customerid = (int)$USER->id;
        } else {
            $token = trim((string)(
                $SESSION->local_subscriptions_guest_checkout_token
                ?? ''
            ));

            if ($token !== '') {
                $session =
                    $this->guestsessions->find_by_token(
                        $token
                    );

                if (
                    $session !== null
                    && !$session->is_expired()
                    && $session->get_user_id() !== null
                    && in_array(
                        $session->get_status(),
                        [
                            'provisional',
                            'payment_pending',
                        ],
                        true
                    )
                ) {
                    $customerid =
                        (int)$session->get_user_id();
                }
            }
        }

        $records = (array)(
            $SESSION->{self::CART_SESSION_PROPERTY}
            ?? []
        );

        $winner = '';
        $winnertime = -1;

        foreach ($records as $key => $record) {
            if (!is_array($record)) {
                $record = (array)$record;
            }

            $recordcustomerid =
                (int)($record['customerid'] ?? -1);
            $currency =
                Currency::sanitize(
                    (string)($record['currency'] ?? '')
                );
            $items =
                (array)($record['items'] ?? []);

            if (
                $recordcustomerid !== $customerid
                || $currency === ''
                || $items === []
                || !in_array(
                    $currency,
                    $available,
                    true
                )
            ) {
                continue;
            }

            $timemodified =
                (int)(
                    $record['timemodified']
                    ?? $record['timecreated']
                    ?? 0
                );

            // Stable deterministic tie-breaker: keep the first matching record.
            if ($timemodified > $winnertime) {
                $winner = $currency;
                $winnertime = $timemodified;
            }
        }

        return $winner;
    }
}
