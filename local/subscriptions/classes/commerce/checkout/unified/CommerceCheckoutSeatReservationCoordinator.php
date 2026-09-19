<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;

/**
 * Checkout-specific reservation lease management.
 *
 * Cart holds are short. Entering checkout extends them; launching a payment
 * extends them again so external/embedded provider interaction cannot race
 * the normal cart TTL.
 */
final class CommerceCheckoutSeatReservationCoordinator {
    public const CHECKOUT_TTL = 10 * MINSECS;
    public const PAYMENT_TTL = 10 * MINSECS;

    public function __construct(
        private readonly CommercePedagogicalSeatReservationService $reservations
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        return new self(
            CommercePedagogicalSeatReservationService::create($db)
        );
    }

    public function extend_snapshot(
        CommerceCartSnapshot $snapshot,
        int $now,
        int $ttl,
        string $phase
    ): void {
        $cart = $snapshot->get_cart();

        foreach ($snapshot->get_items() as $calculated) {
            $item = $calculated->get_item();

            $reservation = $this->reservations->renew_lease(
                $item->get_product_sku(),
                $cart->get_uuid(),
                $cart->get_customer_id(),
                $item->get_quantity(),
                $now,
                $ttl,
                $phase
            );

            $metadata = $item->get_metadata();
            $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
            if (
                $operation === CommercePedagogicalPromotionJoinOperation::OPERATION
                && $reservation !== null
            ) {
                $pinnedpromotionid = (int)($metadata['promotion_join_promotion_id'] ?? 0);
                if (
                    $pinnedpromotionid <= 0
                    || $reservation->get_promotion_id() !== $pinnedpromotionid
                ) {
                    $this->reservations->release_product(
                        $item->get_product_sku(),
                        $cart->get_uuid(),
                        $now
                    );
                    throw new CommercePedagogicalSeatReservationException(
                        'promotion_join_context_changed',
                        'The promotion join reservation no longer matches the pinned cohort.'
                    );
                }
            }
        }
    }
}
