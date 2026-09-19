<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\flow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartMessage;
use local_subscriptions\commerce\cart\domain\CommerceCartOperationResult;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

/**
 * Moves an abandoned Buy Now promotion_join into the normal cart.
 *
 * The seat hold is rebound atomically instead of waiting for its TTL or
 * releasing/re-reserving it. This lets a customer start with "Rejoindre la
 * promotion", return to Storefront, and then build a mixed cart.
 */
final class CommerceDirectPromotionJoinCartTransferService {
    public function __construct(
        private readonly CommerceCartService $carts,
        private readonly CommercePedagogicalSeatReservationService $reservations
    ) {
    }

    public function add_current_to_cart(
        int $customerid,
        string $currency,
        string $language,
        string $productsku,
        int $priceid,
        int $quantity,
        array $metadata,
        ?int $at = null
    ): CommerceCartOperationResult {
        $sku = strtoupper(trim($productsku));
        $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
        $direct = CommerceDirectPurchaseSession::current_any_currency();

        if (
            $operation !== CommercePedagogicalPromotionJoinOperation::OPERATION
            || $direct === null
            || $direct['sku'] !== $sku
            || strtolower(trim((string)($direct['metadata']['operation'] ?? '')))
                !== CommercePedagogicalPromotionJoinOperation::OPERATION
            || $direct['cartuuid'] === null
        ) {
            return $this->carts->add_product(
                $customerid,
                $currency,
                $language,
                $sku,
                $priceid,
                $quantity,
                $metadata,
                $at
            );
        }

        $now = $at ?? time();
        $sourceuuid = (string)$direct['cartuuid'];

        // A stale Direct Purchase session must never let one account adopt a
        // hold created by another authenticated customer.
        $sourceactive = $this->reservations->has_active_hold_for_customer(
            $sku,
            $sourceuuid,
            $customerid,
            $now
        );

        if (!$sourceactive) {
            $result = $this->carts->add_product(
                $customerid,
                $currency,
                $language,
                $sku,
                $priceid,
                $quantity,
                $metadata,
                $now
            );
            if ($result->has_changed()) {
                CommerceDirectPurchaseSession::clear();
            }
            return $result;
        }

        // Validate current eligibility/owner price while the original seat is
        // still safely held by the Direct Purchase. No second seat is reserved.
        $validated = $this->carts->prepare_direct_product(
            $customerid,
            $currency,
            $language,
            $sku,
            $priceid,
            $quantity,
            $metadata,
            $now,
            null,
            null,
            false,
            $sourceuuid
        );
        if (!$validated->has_changed()) {
            return $validated;
        }

        // A stable product may be reused by successive cohorts. The Direct
        // Purchase is already pinned to one promotion in its canonical
        // metadata, so converting it into the normal cart must never spill
        // into a newer cohort selected by the current public sale window.
        $sourcepromotionid = (int)(
            $direct['metadata']['promotion_join_promotion_id'] ?? 0
        );
        $validateditems = $validated->get_cart()->get_items();
        $validateditem = count($validateditems) === 1
            ? reset($validateditems)
            : false;
        $targetpromotionid = $validateditem === false
            ? 0
            : (int)(
                $validateditem->get_metadata()['promotion_join_promotion_id']
                ?? 0
            );

        if (
            $sourcepromotionid <= 0
            || $targetpromotionid <= 0
            || $sourcepromotionid !== $targetpromotionid
        ) {
            return new CommerceCartOperationResult(
                $this->carts->open($customerid, $currency),
                false,
                [new CommerceCartMessage(
                    'promotion_join_context_changed',
                    CommerceCartMessage::LEVEL_WARNING,
                    ['productsku' => $sku]
                )]
            );
        }

        $targetcart = $this->carts->open($customerid, $currency);
        $targetuuid = $targetcart->get_uuid();

        $moved = $this->reservations->transfer_product(
            $sku,
            $sourceuuid,
            $targetuuid,
            $customerid,
            $now
        );
        if (!$moved) {
            // The hold may have expired exactly between validation and the
            // transfer. Fall back to the normal fresh reservation path.
            $result = $this->carts->add_product(
                $customerid,
                $currency,
                $language,
                $sku,
                $priceid,
                $quantity,
                $metadata,
                $now
            );
            if ($result->has_changed()) {
                CommerceDirectPurchaseSession::clear();
            }
            return $result;
        }

        $result = $this->carts->add_product(
            $customerid,
            $currency,
            $language,
            $sku,
            $priceid,
            $quantity,
            $metadata,
            $now
        );

        if (!$result->has_changed()) {
            // Keep the original Direct Purchase resumable if the normal cart
            // rejects the line for any unrelated reason.
            $this->reservations->transfer_product(
                $sku,
                $targetuuid,
                $sourceuuid,
                $customerid,
                $now
            );
            return $result;
        }

        CommerceDirectPurchaseSession::clear();
        return $result;
    }
}
