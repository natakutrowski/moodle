<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\reservation;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;

final class CommercePedagogicalSeatReservationService {
    public const DEFAULT_TTL = 15 * MINSECS;

    public function __construct(
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalSeatReservationRepository $reservations,
        private readonly CommercePedagogicalCapacityService $capacity
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;
        $db = $db ?? $DB;

        $reservations =
            CommercePedagogicalSeatReservationRepository::create($db);

        return new self(
            CommercePedagogicalPromotionOfferRepository::create($db),
            $reservations,
            CommercePedagogicalCapacityService::create($db)
        );
    }


    public function is_pedagogically_linked(string $productsku): bool {
        return $this->offers->has_link_for_product(
            strtoupper(trim($productsku))
        );
    }

    public function has_active_hold(
        string $productsku,
        string $cartuuid,
        int $now
    ): bool {
        $cartuuid = strtolower(trim($cartuuid));
        if (!preg_match('/^[a-f0-9]{32}$/', $cartuuid)) {
            return false;
        }

        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return false;
        }

        $reservation = $this->reservations->find_for_cart_product(
            $cartuuid,
            $productid
        );

        return $reservation !== null
            && $reservation->is_active_at($now);
    }

    public function has_active_hold_for_customer(
        string $productsku,
        string $cartuuid,
        int $customerid,
        int $now
    ): bool {
        if ($customerid <= 0) {
            return false;
        }

        $cartuuid = strtolower(trim($cartuuid));
        if (!preg_match('/^[a-f0-9]{32}$/', $cartuuid)) {
            return false;
        }

        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return false;
        }

        $reservation = $this->reservations->find_for_cart_product(
            $cartuuid,
            $productid
        );

        return $reservation !== null
            && $reservation->get_customer_id() === $customerid
            && $reservation->is_active_at($now);
    }

    /**
     * Atomically holds pedagogical seats for one cart/product.
     *
     * Returns null for products not linked to a pedagogical promotion.
     */
    public function reserve(
        string $productsku,
        string $cartuuid,
        int $customerid,
        int $quantity,
        int $now,
        int $ttl = self::DEFAULT_TTL
    ): ?CommercePedagogicalSeatReservation {
        $sku = strtoupper(trim($productsku));
        $cartuuid = strtolower(trim($cartuuid));

        if (!preg_match('/^[a-f0-9]{32}$/', $cartuuid)) {
            throw new \coding_exception(
                'Pedagogical reservation cart UUID must contain 32 hexadecimal characters.'
            );
        }

        if ($customerid < 0) {
            throw new \coding_exception(
                'Pedagogical reservation customer identifier cannot be negative.'
            );
        }

        if ($quantity <= 0) {
            throw new \coding_exception(
                'Pedagogical reservation quantity must be positive.'
            );
        }

        if ($ttl <= 0) {
            throw new \coding_exception(
                'Pedagogical reservation TTL must be positive.'
            );
        }

        $link = $this->offers->sale_link_for_product($sku, $now);
        if ($link === null) {
            return null;
        }

        $promotionid = (int)$link['promotion']->get_id();
        $productid = (int)$link['offer']->productid;

        return $this->with_promotion_lock(
            $promotionid,
            function () use (
                $sku,
                $promotionid,
                $productid,
                $cartuuid,
                $customerid,
                $quantity,
                $now,
                $ttl
            ): CommercePedagogicalSeatReservation {
                $this->reservations->expire_due($now);

                if (
                    $customerid > 0
                    && $this->reservations->has_active_for_customer_offer(
                        $promotionid,
                        $productid,
                        $customerid,
                        $now,
                        $cartuuid
                    )
                ) {
                    throw new CommercePedagogicalSeatReservationException(
                        'pedagogical_customer_hold_exists',
                        'This customer already has an active hold for this pedagogical offer.'
                    );
                }

                // Exclude only this cart's hold for this exact product.
                // Other holds belonging to the same cart still consume the
                // promotion-wide capacity.
                $snapshot = $this->capacity->for_product(
                    $sku,
                    $now,
                    $cartuuid
                );

                if (!$snapshot->are_sales_open()) {
                    throw new CommercePedagogicalSeatReservationException(
                        CommercePedagogicalCapacityService::SALES_CLOSED,
                        'Sales are closed for this pedagogical promotion.'
                    );
                }

                $remaining = $snapshot->get_remaining();
                if (
                    !$snapshot->is_available()
                    || (
                        $remaining !== null
                        && $remaining < $quantity
                    )
                ) {
                    throw new CommercePedagogicalSeatReservationException(
                        $snapshot->get_blocking_reason()
                            ?? CommercePedagogicalCapacityService::OFFER_FULL,
                        'Not enough pedagogical seats are available.'
                    );
                }

                $existing = $this->reservations->find(
                    $promotionid,
                    $productid,
                    $cartuuid
                );

                $isactive = $existing?->is_active_at($now) ?? false;
                $createdat = $isactive
                    ? $existing->get_time_created()
                    : $now;
                $checkoutstartedat = $isactive
                    ? $existing->get_checkout_started_at()
                    : null;
                $paymentstartedat = $isactive
                    ? $existing->get_payment_started_at()
                    : null;

                $expiresat = $now + $ttl;
                if ($isactive && ($checkoutstartedat !== null || $paymentstartedat !== null)) {
                    // Once checkout has started, generic cart renewals must not
                    // move the anchored deadline. Only renew_lease() may grant
                    // the one-time minimum associated with a new phase.
                    $expiresat = $existing->get_expires_at();
                }

                return $this->reservations->save(
                    new CommercePedagogicalSeatReservation(
                        $existing?->get_id(),
                        $promotionid,
                        $productid,
                        $cartuuid,
                        $customerid,
                        $quantity,
                        CommercePedagogicalSeatReservation::ACTIVE,
                        $expiresat,
                        $checkoutstartedat,
                        $paymentstartedat,
                        null,
                        $createdat,
                        $now
                    )
                );
            }
        );
    }

    public function renew(
        string $productsku,
        string $cartuuid,
        int $customerid,
        int $quantity,
        int $now,
        int $ttl = self::DEFAULT_TTL
    ): ?CommercePedagogicalSeatReservation {
        return $this->reserve(
            $productsku,
            $cartuuid,
            $customerid,
            $quantity,
            $now,
            $ttl
        );
    }

    /**
     * Reacquire an expired/released hold without ever changing its cohort.
     *
     * This is used only after a provider has confirmed payment. A stable
     * Commerce SKU can be reused by successive cohorts, so falling back to
     * reserve() would be unsafe once the original cart row has pinned a
     * promotion.
     */
    public function renew_pinned(
        string $productsku,
        string $cartuuid,
        int $customerid,
        int $quantity,
        int $now,
        int $ttl = self::DEFAULT_TTL
    ): ?CommercePedagogicalSeatReservation {
        $sku = strtoupper(trim($productsku));
        $cartuuid = strtolower(trim($cartuuid));

        if (!preg_match('/^[a-f0-9]{32}$/', $cartuuid)) {
            throw new \coding_exception(
                'Pedagogical reservation cart UUID must contain 32 hexadecimal characters.'
            );
        }
        if ($customerid < 0 || $quantity <= 0 || $ttl <= 0) {
            throw new \coding_exception(
                'Invalid pedagogical paid-purchase reservation renewal.'
            );
        }

        $productid = $this->linked_product_id($sku);
        if ($productid === null) {
            return null;
        }

        $existing = $this->reservations->find_for_cart_product(
            $cartuuid,
            $productid
        );
        if ($existing === null) {
            return $this->reserve(
                $sku,
                $cartuuid,
                $customerid,
                $quantity,
                $now,
                $ttl
            );
        }

        if (
            $existing->get_customer_id() !== $customerid
            || $existing->get_quantity() !== $quantity
        ) {
            throw new \coding_exception(
                'Pinned pedagogical reservation does not match the paid customer or quantity.'
            );
        }

        if ($existing->get_state() === CommercePedagogicalSeatReservation::CONSUMED) {
            throw new \coding_exception(
                'A consumed pedagogical reservation cannot be reacquired by another paid purchase.'
            );
        }

        // A still-valid payment hold is already authoritative. Do not move
        // its anchored expiry merely because the provider callback is being
        // processed or replayed. Paid fulfillment only reacquires after the
        // original hold has actually expired/released.
        if ($existing->is_active_at($now)) {
            return $existing;
        }

        $promotionid = $existing->get_promotion_id();
        $productid = $existing->get_product_id();

        return $this->with_promotion_lock(
            $promotionid,
            function () use (
                $sku,
                $promotionid,
                $productid,
                $cartuuid,
                $customerid,
                $quantity,
                $now,
                $ttl
            ): CommercePedagogicalSeatReservation {
                $this->reservations->expire_due($now);

                $link = $this->offers->link_for_promotion_and_product(
                    $promotionid,
                    $productid
                );
                if ($link === null) {
                    throw new \coding_exception(
                        'Pinned pedagogical reservation points to a missing promotion offer link.'
                    );
                }

                $snapshot = $this->capacity->for_promotion_product(
                    $sku,
                    $promotionid,
                    $productid,
                    $now,
                    $cartuuid
                );
                if (!$snapshot->is_pedagogically_linked()) {
                    throw new \coding_exception(
                        'Pinned pedagogical reservation no longer matches the Commerce product.'
                    );
                }
                if (!$snapshot->are_sales_open()) {
                    throw new CommercePedagogicalSeatReservationException(
                        CommercePedagogicalCapacityService::SALES_CLOSED,
                        'Sales are closed for the pinned pedagogical promotion.'
                    );
                }

                $remaining = $snapshot->get_remaining();
                if (
                    !$snapshot->is_available()
                    || ($remaining !== null && $remaining < $quantity)
                ) {
                    throw new CommercePedagogicalSeatReservationException(
                        $snapshot->get_blocking_reason()
                            ?? CommercePedagogicalCapacityService::OFFER_FULL,
                        'Not enough seats remain in the pinned pedagogical promotion.'
                    );
                }

                $current = $this->reservations->find(
                    $promotionid,
                    $productid,
                    $cartuuid
                );
                if (
                    $current !== null
                    && $current->get_state() === CommercePedagogicalSeatReservation::CONSUMED
                ) {
                    throw new \coding_exception(
                        'A consumed pedagogical reservation cannot be reacquired by another paid purchase.'
                    );
                }

                $createdat = $current !== null && $current->is_active_at($now)
                    ? $current->get_time_created()
                    : $now;
                $checkoutstartedat = $current !== null && $current->is_active_at($now)
                    ? $current->get_checkout_started_at()
                    : null;
                $paymentstartedat = $current !== null && $current->is_active_at($now)
                    ? $current->get_payment_started_at()
                    : null;

                return $this->reservations->save(
                    new CommercePedagogicalSeatReservation(
                        $current?->get_id(),
                        $promotionid,
                        $productid,
                        $cartuuid,
                        $customerid,
                        $quantity,
                        CommercePedagogicalSeatReservation::ACTIVE,
                        $now + $ttl,
                        $checkoutstartedat,
                        $paymentstartedat,
                        null,
                        $createdat,
                        $now
                    )
                );
            }
        );
    }

    public function renew_lease(
        string $productsku,
        string $cartuuid,
        int $customerid,
        int $quantity,
        int $now,
        int $ttl,
        string $phase
    ): ?CommercePedagogicalSeatReservation {
        if (!in_array($phase, ['checkout', 'payment'], true)) {
            throw new \coding_exception('Unknown pedagogical reservation lease phase.');
        }

        $cartuuid = strtolower(trim($cartuuid));
        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return null;
        }

        $existing = $this->reservations->find_for_cart_product(
            $cartuuid,
            $productid
        );

        if ($existing !== null && !$existing->is_active_at($now)) {
            $phasealreadystarted = $phase === 'payment'
                ? $existing->get_payment_started_at() !== null
                : $existing->get_checkout_started_at() !== null;
            if ($phasealreadystarted) {
                // Reloading an expired checkout/payment must never silently
                // reacquire a seat, even if a newer cohort now sells the same
                // stable Commerce product.
                $this->reservations->expire_due($now);
                return $this->reservations->find(
                    $existing->get_promotion_id(),
                    $existing->get_product_id(),
                    $cartuuid
                );
            }
            $existing = null;
        }

        if ($existing !== null) {
            $link = $this->offers->link_for_promotion_and_product(
                $existing->get_promotion_id(),
                $existing->get_product_id()
            );
            if ($link === null) {
                throw new \coding_exception(
                    'Pedagogical reservation points to a missing promotion offer link.'
                );
            }
            if (!$link['promotion']->sales_are_open($now)) {
                throw new CommercePedagogicalSeatReservationException(
                    CommercePedagogicalCapacityService::SALES_CLOSED,
                    'Sales are closed for this pedagogical promotion.'
                );
            }
            $reservation = $existing;
        } else {
            $reservation = $this->reserve(
                $productsku,
                $cartuuid,
                $customerid,
                $quantity,
                $now,
                $ttl
            );
            if ($reservation === null) {
                return null;
            }
        }

        $checkoutstartedat = $reservation->get_checkout_started_at();
        $paymentstartedat = $reservation->get_payment_started_at();
        if ($phase === 'checkout' && $checkoutstartedat === null) {
            $checkoutstartedat = $now;
        }
        if ($phase === 'payment' && $paymentstartedat === null) {
            $paymentstartedat = $now;
        }

        $cap = $phase === 'payment'
            ? $paymentstartedat + $ttl
            : $checkoutstartedat + $ttl;
        $expiresat = max($reservation->get_expires_at(), $cap);

        return $this->reservations->save(
            new CommercePedagogicalSeatReservation(
                $reservation->get_id(),
                $reservation->get_promotion_id(),
                $reservation->get_product_id(),
                $reservation->get_cart_uuid(),
                $customerid,
                $reservation->get_quantity(),
                CommercePedagogicalSeatReservation::ACTIVE,
                $expiresat,
                $checkoutstartedat,
                $paymentstartedat,
                null,
                $reservation->get_time_created(),
                $now
            )
        );
    }

    /**
     * Rebind one product hold between cart UUIDs without releasing the seat.
     *
     * Used when an owner starts with Buy Now and then decides to place the
     * same promotion_join in the normal cart. Only the requested product is
     * moved; unrelated holds already present in the target cart are untouched.
     */
    public function transfer_product(
        string $productsku,
        string $sourcecartuuid,
        string $targetcartuuid,
        int $targetcustomerid,
        int $now
    ): bool {
        $sourcecartuuid = strtolower(trim($sourcecartuuid));
        $targetcartuuid = strtolower(trim($targetcartuuid));

        foreach ([$sourcecartuuid, $targetcartuuid] as $uuid) {
            if (!preg_match('/^[a-f0-9]{32}$/', $uuid)) {
                throw new \coding_exception(
                    'Pedagogical reservation cart UUID must contain 32 hexadecimal characters.'
                );
            }
        }
        if ($targetcustomerid < 0) {
            throw new \coding_exception(
                'Pedagogical reservation transfer customer identifier cannot be negative.'
            );
        }

        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return false;
        }

        $this->reservations->expire_due($now);
        $source = $this->reservations->find_for_cart_product(
            $sourcecartuuid,
            $productid
        );
        if (
            $source === null
            || !$source->is_active_at($now)
            || $source->get_customer_id() !== $targetcustomerid
        ) {
            // Product-scoped transfer is deliberately same-customer only.
            // Identity migrations belong to transfer_cart(), which has its own
            // explicit reconciliation contract.
            return false;
        }

        $promotionid = $source->get_promotion_id();

        return $this->with_promotion_lock(
            $promotionid,
            function () use (
                $sourcecartuuid,
                $targetcartuuid,
                $targetcustomerid,
                $promotionid,
                $productid,
                $now
            ): bool {
                $current = $this->reservations->find(
                    $promotionid,
                    $productid,
                    $sourcecartuuid
                );
                if (
                    $current === null
                    || !$current->is_active_at($now)
                    || $current->get_customer_id() !== $targetcustomerid
                ) {
                    return false;
                }

                if ($sourcecartuuid === $targetcartuuid) {
                    if ($current->get_customer_id() === $targetcustomerid) {
                        return false;
                    }
                    $this->reservations->save(
                        new CommercePedagogicalSeatReservation(
                            $current->get_id(),
                            $promotionid,
                            $productid,
                            $targetcartuuid,
                            $targetcustomerid,
                            $current->get_quantity(),
                            CommercePedagogicalSeatReservation::ACTIVE,
                            $current->get_expires_at(),
                            $current->get_checkout_started_at(),
                            $current->get_payment_started_at(),
                            null,
                            $current->get_time_created(),
                            $now
                        )
                    );
                    return true;
                }

                $target = $this->reservations->find(
                    $promotionid,
                    $productid,
                    $targetcartuuid
                );

                if ($target === null) {
                    $this->reservations->save(
                        new CommercePedagogicalSeatReservation(
                            $current->get_id(),
                            $promotionid,
                            $productid,
                            $targetcartuuid,
                            $targetcustomerid,
                            $current->get_quantity(),
                            CommercePedagogicalSeatReservation::ACTIVE,
                            $current->get_expires_at(),
                            $current->get_checkout_started_at(),
                            $current->get_payment_started_at(),
                            null,
                            $current->get_time_created(),
                            $now
                        )
                    );
                    return true;
                }

                $targetactive = $target->is_active_at($now);
                $expiresat = $targetactive
                    ? max($current->get_expires_at(), $target->get_expires_at())
                    : $current->get_expires_at();
                $quantity = $targetactive
                    ? max($current->get_quantity(), $target->get_quantity())
                    : $current->get_quantity();
                $checkoutstartedat = self::earliest_anchor(
                    $current->get_checkout_started_at(),
                    $targetactive ? $target->get_checkout_started_at() : null
                );
                $paymentstartedat = self::earliest_anchor(
                    $current->get_payment_started_at(),
                    $targetactive ? $target->get_payment_started_at() : null
                );
                $timecreated = $targetactive
                    ? min($current->get_time_created(), $target->get_time_created())
                    : $current->get_time_created();

                $this->reservations->save(
                    new CommercePedagogicalSeatReservation(
                        $target->get_id(),
                        $promotionid,
                        $productid,
                        $targetcartuuid,
                        $targetcustomerid,
                        $quantity,
                        CommercePedagogicalSeatReservation::ACTIVE,
                        $expiresat,
                        $checkoutstartedat,
                        $paymentstartedat,
                        null,
                        $timecreated,
                        $now
                    )
                );
                $this->reservations->save(
                    new CommercePedagogicalSeatReservation(
                        $current->get_id(),
                        $promotionid,
                        $productid,
                        $sourcecartuuid,
                        $current->get_customer_id(),
                        $current->get_quantity(),
                        CommercePedagogicalSeatReservation::RELEASED,
                        $current->get_expires_at(),
                        $current->get_checkout_started_at(),
                        $current->get_payment_started_at(),
                        null,
                        $current->get_time_created(),
                        $now
                    )
                );
                return true;
            }
        );
    }

    /**
     * Rebind active pedagogical holds when a cart identity/UUID changes.
     *
     * This is intentionally atomic per promotion: any cart identity change
     * (guest -> resolved user, currency switch, etc.) must never release the
     * last seat and then try to reserve it again. Anonymous customer id 0 is a
     * valid target; negative identifiers remain invalid. Existing target holds
     * for the same offer are reconciled instead of double-counted.
     */
    public function transfer_cart(
        string $sourcecartuuid,
        string $targetcartuuid,
        int $targetcustomerid,
        int $now
    ): int {
        $sourcecartuuid = strtolower(trim($sourcecartuuid));
        $targetcartuuid = strtolower(trim($targetcartuuid));

        foreach ([$sourcecartuuid, $targetcartuuid] as $uuid) {
            if (!preg_match('/^[a-f0-9]{32}$/', $uuid)) {
                throw new \coding_exception(
                    'Pedagogical reservation cart UUID must contain 32 hexadecimal characters.'
                );
            }
        }
        if ($targetcustomerid < 0) {
            throw new \coding_exception(
                'Pedagogical reservation transfer customer identifier cannot be negative.'
            );
        }

        $this->reservations->expire_due($now);
        $sources = $this->reservations->active_for_cart($sourcecartuuid, $now);
        $transferred = 0;

        foreach ($sources as $source) {
            $promotionid = $source->get_promotion_id();
            $productid = $source->get_product_id();

            $changed = $this->with_promotion_lock(
                $promotionid,
                function () use (
                    $sourcecartuuid,
                    $targetcartuuid,
                    $targetcustomerid,
                    $promotionid,
                    $productid,
                    $now
                ): bool {
                    $current = $this->reservations->find(
                        $promotionid,
                        $productid,
                        $sourcecartuuid
                    );
                    if ($current === null || !$current->is_active_at($now)) {
                        return false;
                    }

                    if ($sourcecartuuid === $targetcartuuid) {
                        if ($current->get_customer_id() === $targetcustomerid) {
                            return false;
                        }
                        $this->reservations->save(
                            new CommercePedagogicalSeatReservation(
                                $current->get_id(),
                                $current->get_promotion_id(),
                                $current->get_product_id(),
                                $current->get_cart_uuid(),
                                $targetcustomerid,
                                $current->get_quantity(),
                                CommercePedagogicalSeatReservation::ACTIVE,
                                $current->get_expires_at(),
                                $current->get_checkout_started_at(),
                                $current->get_payment_started_at(),
                                null,
                                $current->get_time_created(),
                                $now
                            )
                        );
                        return true;
                    }

                    $target = $this->reservations->find(
                        $promotionid,
                        $productid,
                        $targetcartuuid
                    );

                    if ($target === null) {
                        // Rebind the existing row itself: there is no
                        // release/re-reserve window and the original deadline
                        // is preserved byte-for-byte.
                        $this->reservations->save(
                            new CommercePedagogicalSeatReservation(
                                $current->get_id(),
                                $current->get_promotion_id(),
                                $current->get_product_id(),
                                $targetcartuuid,
                                $targetcustomerid,
                                $current->get_quantity(),
                                CommercePedagogicalSeatReservation::ACTIVE,
                                $current->get_expires_at(),
                                $current->get_checkout_started_at(),
                                $current->get_payment_started_at(),
                                null,
                                $current->get_time_created(),
                                $now
                            )
                        );
                        return true;
                    }

                    // A target row already exists (for example from an older
                    // provisional checkout). Reconcile both holds into the
                    // target without extending either phase deadline.
                    $targetactive = $target->is_active_at($now);
                    $expiresat = $targetactive
                        ? max($current->get_expires_at(), $target->get_expires_at())
                        : $current->get_expires_at();
                    $quantity = $targetactive
                        ? max($current->get_quantity(), $target->get_quantity())
                        : $current->get_quantity();
                    $checkoutstartedat = self::earliest_anchor(
                        $current->get_checkout_started_at(),
                        $targetactive ? $target->get_checkout_started_at() : null
                    );
                    $paymentstartedat = self::earliest_anchor(
                        $current->get_payment_started_at(),
                        $targetactive ? $target->get_payment_started_at() : null
                    );
                    $timecreated = $targetactive
                        ? min($current->get_time_created(), $target->get_time_created())
                        : $current->get_time_created();

                    $this->reservations->save(
                        new CommercePedagogicalSeatReservation(
                            $target->get_id(),
                            $promotionid,
                            $productid,
                            $targetcartuuid,
                            $targetcustomerid,
                            $quantity,
                            CommercePedagogicalSeatReservation::ACTIVE,
                            $expiresat,
                            $checkoutstartedat,
                            $paymentstartedat,
                            null,
                            $timecreated,
                            $now
                        )
                    );

                    $this->reservations->save(
                        new CommercePedagogicalSeatReservation(
                            $current->get_id(),
                            $current->get_promotion_id(),
                            $current->get_product_id(),
                            $current->get_cart_uuid(),
                            $current->get_customer_id(),
                            $current->get_quantity(),
                            CommercePedagogicalSeatReservation::RELEASED,
                            $current->get_expires_at(),
                            $current->get_checkout_started_at(),
                            $current->get_payment_started_at(),
                            null,
                            $current->get_time_created(),
                            $now
                        )
                    );
                    return true;
                }
            );

            if ($changed) {
                $transferred++;
            }
        }

        return $transferred;
    }

    private static function earliest_anchor(?int $left, ?int $right): ?int {
        if ($left === null) {
            return $right;
        }
        if ($right === null) {
            return $left;
        }
        return min($left, $right);
    }

    public function release_cart(
        string $cartuuid,
        int $now
    ): int {
        return $this->reservations->release_cart(
            strtolower(trim($cartuuid)),
            $now
        );
    }

    public function release_product(
        string $productsku,
        string $cartuuid,
        int $now
    ): bool {
        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return false;
        }

        $cartuuid = strtolower(trim($cartuuid));
        $existing = $this->reservations->find_for_cart_product(
            $cartuuid,
            $productid
        );
        if (
            $existing === null
            || $existing->get_state()
                !== CommercePedagogicalSeatReservation::ACTIVE
        ) {
            return false;
        }

        $promotionid = $existing->get_promotion_id();
        return $this->with_promotion_lock(
            $promotionid,
            function () use (
                $promotionid,
                $productid,
                $cartuuid,
                $now
            ): bool {
                $current = $this->reservations->find(
                    $promotionid,
                    $productid,
                    $cartuuid
                );
                if (
                    $current === null
                    || $current->get_state()
                        !== CommercePedagogicalSeatReservation::ACTIVE
                ) {
                    return false;
                }

                $this->reservations->save(
                    new CommercePedagogicalSeatReservation(
                        $current->get_id(),
                        $current->get_promotion_id(),
                        $current->get_product_id(),
                        $current->get_cart_uuid(),
                        $current->get_customer_id(),
                        $current->get_quantity(),
                        CommercePedagogicalSeatReservation::RELEASED,
                        $current->get_expires_at(),
                        $current->get_checkout_started_at(),
                        $current->get_payment_started_at(),
                        null,
                        $current->get_time_created(),
                        $now
                    )
                );
                return true;
            }
        );
    }

    /**
     * True when this exact cart/product hold was already consumed by the same
     * purchase. Replayed paid fulfillment must preserve that terminal state.
     */
    public function is_consumed_by_purchase(
        string $productsku,
        string $cartuuid,
        string $purchasereference
    ): bool {
        $reference = trim($purchasereference);
        if ($reference === '') {
            return false;
        }

        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return false;
        }

        $reservation = $this->reservations->find_for_cart_product(
            strtolower(trim($cartuuid)),
            $productid
        );

        return $reservation !== null
            && $reservation->get_state()
                === CommercePedagogicalSeatReservation::CONSUMED
            && hash_equals(
                (string)$reservation->get_purchase_reference(),
                $reference
            );
    }

    public function consume(
        string $productsku,
        string $cartuuid,
        string $purchasereference,
        int $now
    ): bool {
        $reference = trim($purchasereference);
        if ($reference === '') {
            throw new \coding_exception(
                'Pedagogical reservation purchase reference is required.'
            );
        }

        $productid = $this->linked_product_id($productsku);
        if ($productid === null) {
            return false;
        }

        $cartuuid = strtolower(trim($cartuuid));
        $existing = $this->reservations->find_for_cart_product(
            $cartuuid,
            $productid
        );
        if ($existing === null) {
            return false;
        }

        $promotionid = $existing->get_promotion_id();
        return $this->with_promotion_lock(
            $promotionid,
            function () use (
                $promotionid,
                $productid,
                $cartuuid,
                $reference,
                $now
            ): bool {
                $current = $this->reservations->find(
                    $promotionid,
                    $productid,
                    $cartuuid
                );
                if ($current === null) {
                    return false;
                }

                if (
                    $current->get_state()
                    === CommercePedagogicalSeatReservation::CONSUMED
                ) {
                    return hash_equals(
                        (string)$current->get_purchase_reference(),
                        $reference
                    );
                }

                if (
                    $current->get_state()
                    !== CommercePedagogicalSeatReservation::ACTIVE
                    || $current->get_expires_at() <= $now
                ) {
                    return false;
                }

                $this->reservations->save(
                    new CommercePedagogicalSeatReservation(
                        $current->get_id(),
                        $current->get_promotion_id(),
                        $current->get_product_id(),
                        $current->get_cart_uuid(),
                        $current->get_customer_id(),
                        $current->get_quantity(),
                        CommercePedagogicalSeatReservation::CONSUMED,
                        $current->get_expires_at(),
                        $current->get_checkout_started_at(),
                        $current->get_payment_started_at(),
                        $reference,
                        $current->get_time_created(),
                        $now
                    )
                );

                return true;
            }
        );
    }

    public function expire_due(int $now): int {
        return $this->reservations->expire_due($now);
    }

    private function linked_product_id(string $productsku): ?int {
        $links = $this->offers->links_for_product(
            strtoupper(trim($productsku))
        );
        if ($links === []) {
            return null;
        }
        return (int)$links[0]['offer']->productid;
    }

    private function with_promotion_lock(
        int $promotionid,
        callable $operation
    ): mixed {
        $factory = lock_config::get_lock_factory(
            'local_subscriptions_commerce_pedagogical_capacity'
        );

        // Promotion-wide, not product-wide: RU and FR offers may compete
        // for the same final global promotion seat.
        $lock = $factory->get_lock(
            'promotion:' . $promotionid,
            10
        );

        if ($lock === false) {
            throw new \RuntimeException(
                'Unable to acquire pedagogical capacity lock.'
            );
        }

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }
}
