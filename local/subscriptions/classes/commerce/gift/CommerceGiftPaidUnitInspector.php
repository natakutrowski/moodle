<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePurchaseGrantPlanner;

/**
 * Reads authoritative persisted Native payment and checkout snapshots.
 * No payment can be marked paid or inferred from a return URL here.
 * G3A deliberately limits issuance to one Reader product, one purchase line,
 * one quantity, and a signed future checkout-owned gift intent.
 */
final class CommerceGiftPaidUnitInspector {
    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @return array{purchase:\stdClass,item:\stdClass,intent:array,priceid:int,paymentid:int,rights:array}
     */
    public function inspect(int $purchaseid, int $itemid, string $secretkeyhex): array {
        if ($purchaseid <= 0 || $itemid <= 0) {
            throw new \InvalidArgumentException('Native purchase and item IDs must be positive.');
        }
        $purchase = $this->db->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE, ['id' => $purchaseid], '*', MUST_EXIST
        );
        $item = $this->db->get_record(
            CommercePersistenceSchema::TABLE_ITEM,
            ['id' => $itemid, 'purchaseid' => $purchaseid], '*', MUST_EXIST
        );
        // Native Commerce normally keeps the purchase in payment_pending until
        // paid-completion finishes. The payment attempt itself is the authoritative
        // settled-payment evidence, checked below (paidat, amount, currency,
        // refund state and single attempt). Never infer payment from return URLs.
        // This is intentionally stricter than accepting arbitrary purchase states.
        if (!in_array((string)$purchase->status, ['payment_pending', 'paid'], true)) {
            throw new \DomainException('Gift issuance requires a payment-pending or paid Native purchase.');
        }
        // Initial G3A scope: no split payments, combinations or multi-quantity semantics.
        $items = $this->db->get_records(CommercePersistenceSchema::TABLE_ITEM, ['purchaseid' => $purchaseid]);
        if (count($items) !== 1 || (int)$item->quantity !== 1) {
            throw new \DomainException('G3A only supports a single one-unit purchase.');
        }
        if (strtoupper((string)$item->currency) !== strtoupper((string)$purchase->currency)
            || (int)$item->netminor <= 0
            || (int)$item->netminor !== (int)$purchase->totalminor) {
            throw new \DomainException('Gift purchase and line totals do not match.');
        }
        $sku = strtoupper(trim((string)$item->itemreference));
        if ($sku !== 'AVENT_2026') {
            throw new \DomainException('G3A issue pilot is limited to AVENT_2026.');
        }
        $payments = $this->db->get_records(CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => $purchaseid]);
        $confirmed = [];
        foreach ($payments as $payment) {
            if ((string)$payment->status === 'refunded') {
                throw new \DomainException('Refunded payment attempt is not eligible for a gift.');
            }
            if (in_array((string)$payment->status, ['paid', 'completed'], true)
                && (int)($payment->paidat ?? 0) > 0
                && strtoupper((string)$payment->currency) === strtoupper((string)$purchase->currency)
                && (int)$payment->amountminor === (int)$purchase->totalminor) {
                $confirmed[] = (int)$payment->id;
            }
        }
        if (count($confirmed) !== 1) {
            throw new \DomainException('Exactly one settled Native payment attempt is required.');
        }
        // The settled provider MUST match Native's currency route. Never infer
        // RUB from Stripe or accept an alternate provider for a gift.
        $paid = $this->db->get_record(CommercePersistenceSchema::TABLE_PAYMENT,
            ['id' => $confirmed[0], 'purchaseid' => $purchaseid], '*', MUST_EXIST);
        CommerceGiftNativeProviderGuard::assert_paid((string)$purchase->currency,
            (string)$item->currency, (string)$paid->currency, (string)$paid->provider);
        // A separate refund record may exist while the payment attempt remains paid.
        $refunds = $this->db->get_records(CommercePersistenceSchema::TABLE_REFUND,
            ['paymentid' => $confirmed[0]]);
        foreach ($refunds as $refund) {
            if (!in_array((string)$refund->status, ['failed', 'error', 'cancelled'], true)) {
                throw new \DomainException('Gift issuance blocked by a pending or successful refund.');
            }
        }
        if ($this->db->record_exists('local_subs_commerce_grant',
            ['purchasereference' => (string)$purchase->reference])) {
            throw new \DomainException('This purchase already has Native rights; no retroactive gift conversion.');
        }
        $metadata = json_decode((string)$item->metadatajson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($metadata) || !isset($metadata['priceid']) || (int)$metadata['priceid'] <= 0
            || !isset($metadata[CommerceGiftIntentSeal::METADATA_FIELD])
            || !is_array($metadata[CommerceGiftIntentSeal::METADATA_FIELD])) {
            throw new \DomainException('Missing persisted gift intent or price reference.');
        }
        $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
        if ($operation !== '' || !empty($metadata['is_trial']) || !empty($metadata['is_recurring'])) {
            throw new \DomainException('Promotion, trial and renewal operations are not eligible for G3A.');
        }
        $priceid = (int)$metadata['priceid'];
        $intent = CommerceGiftIntentSeal::verify(
            $purchase, $item, $metadata[CommerceGiftIntentSeal::METADATA_FIELD],
            $priceid, $secretkeyhex
        );
        $grants = (new CommerceNativePurchaseGrantPlanner($this->db))->plan(
            $purchase, [$item]
        )->get_grants();
        if (count($grants) !== 1) {
            throw new \DomainException('The paid Reader product must resolve to exactly one Native grant.');
        }
        $grant = $grants[0];
        if ($grant->get_type() !== 'reader_access'
            || $grant->get_product_sku() !== $sku
            || $grant->get_resource_key() !== 'reader:publication:avent-2026'
            || $grant->get_quantity() !== 1) {
            throw new \DomainException('The Native Reader grant does not match the expected Avent product.');
        }
        $duration = $grant->get_valid_until() === null ? null
            : $grant->get_valid_until() - $grant->get_valid_from();
        // No buyer identity, transaction token or grant reference in the gift rights snapshot.
        $rights = [[
            'type' => $grant->get_type(),
            'sku' => $grant->get_product_sku(),
            'resourcekey' => $grant->get_resource_key(),
            'quantity' => $grant->get_quantity(),
            'durationseconds' => $duration,
        ]];
        return compact('purchase', 'item', 'intent', 'priceid', 'rights') + ['paymentid' => $confirmed[0]];
    }
}
