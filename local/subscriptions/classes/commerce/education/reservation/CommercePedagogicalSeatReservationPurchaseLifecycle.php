<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\reservation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSeatReservationCoordinator;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

/**
 * Bridges persisted Native purchases and temporary pedagogical seat holds.
 */
final class CommercePedagogicalSeatReservationPurchaseLifecycle {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalSeatReservationService $reservations
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            $db,
            CommercePedagogicalSeatReservationService::create($db)
        );
    }

    /**
     * Re-acquire/extend every pedagogical hold immediately before fulfillment.
     *
     * This closes the race where the provider confirms payment just after the
     * checkout/payment lease expires. If the seat has genuinely been taken by
     * another cart, reserve() rejects and fulfillment stops rather than
     * overbooking the promotion.
     *
     * @param CommerceEntitlementGrant[] $grants
     */
    public function prepare_paid_purchase(
        \stdClass $purchase,
        array $grants,
        int $now
    ): ?string {
        $cartuuid = $this->cart_uuid_from_purchase($purchase);
        if ($cartuuid === null) {
            return null;
        }

        $purchasereference = trim((string)($purchase->reference ?? ''));

        foreach ($grants as $grant) {
            if (!$grant instanceof CommerceEntitlementGrant) {
                throw new \coding_exception(
                    'Invalid grant passed to pedagogical reservation lifecycle.'
                );
            }

            if (
                $purchasereference !== ''
                && $this->reservations->is_consumed_by_purchase(
                    $grant->get_product_sku(),
                    $cartuuid,
                    $purchasereference
                )
            ) {
                continue;
            }

            $this->reservations->renew_pinned(
                $grant->get_product_sku(),
                $cartuuid,
                $grant->get_beneficiary_user_id() ?? 0,
                max(1, $grant->get_quantity()),
                $now,
                CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL
            );
        }

        return $cartuuid;
    }

    /**
     * Release every seat hold belonging to a failed/expired Native checkout.
     */
    public function release_for_payment_event(
        InternalEvent $event,
        int $now
    ): int {
        $purchase = $this->purchase_from_event($event);
        if ($purchase === null) {
            return 0;
        }

        $cartuuid = $this->cart_uuid_from_purchase($purchase);
        if ($cartuuid === null) {
            return 0;
        }

        return $this->reservations->release_cart(
            $cartuuid,
            $now
        );
    }

    public function cart_uuid_from_purchase(
        \stdClass $purchase
    ): ?string {
        $metadata = $this->decode_metadata(
            (string)($purchase->metadatajson ?? '')
        );
        $cartuuid = strtolower(trim(
            (string)($metadata['cart_uuid'] ?? '')
        ));

        return preg_match('/^[a-f0-9]{32}$/', $cartuuid)
            ? $cartuuid
            : null;
    }

    private function purchase_from_event(
        InternalEvent $event
    ): ?\stdClass {
        $purchaseuuid = strtolower(trim(
            (string)($event->meta['commerce_purchase_uuid'] ?? '')
        ));

        if ($purchaseuuid !== '') {
            $purchase = $this->db->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['purchaseuuid' => $purchaseuuid],
                '*',
                IGNORE_MISSING
            );
            if ($purchase !== false) {
                return $purchase;
            }
        }

        $reference = trim(
            (string)($event->meta['commerce_reference'] ?? '')
        );
        if ($reference === '') {
            return null;
        }

        $purchase = $this->db->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $reference],
            '*',
            IGNORE_MISSING
        );

        return $purchase === false ? null : $purchase;
    }

    /** @return array<string,mixed> */
    private function decode_metadata(string $json): array {
        if (trim($json) === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
