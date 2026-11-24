<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Recheck original checkout evidence before ANY invitation/claim/activation. */
final class CommerceGiftPaidSafety {
    public function __construct(private readonly \moodle_database $db) {}

    public function assert_still_paid(\stdClass $gift): void {
        $purchase = $this->db->get_record(CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => (int)$gift->purchaseid], '*', MUST_EXIST);
        $item = $this->db->get_record(CommercePersistenceSchema::TABLE_ITEM,
            ['id' => (int)$gift->purchaseitemid, 'purchaseid' => (int)$gift->purchaseid], '*', MUST_EXIST);
        if (!in_array((string)$purchase->status, ['paid', 'fulfilled'], true)
            || (int)$item->quantity !== 1
            || strtoupper(trim((string)$item->itemreference)) !== 'AVENT_2026'
            || strtoupper(trim((string)$gift->sourceproductsku)) !== 'AVENT_2026'
            || trim((string)$purchase->customeremail) === ''
            || strtolower(trim((string)$purchase->customeremail)) !== strtolower(trim((string)$gift->buyeremail))) {
            throw new \DomainException('Gift source purchase is not eligible.');
        }
        $snapshot = json_decode((string)$gift->purchasesnapshotjson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($snapshot) || (int)($snapshot['version'] ?? 0) !== 1
            || (string)($snapshot['purchasereference'] ?? '') !== (string)$purchase->reference
            || !is_string($snapshot['intentdigest'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/D', $snapshot['intentdigest']) !== 1
            || (int)($snapshot['paymentid'] ?? 0) <= 0
            || (int)($snapshot['priceid'] ?? 0) !== (int)$gift->sourcepriceid
            || (int)($snapshot['netminor'] ?? -1) !== (int)$item->netminor
            || (int)($snapshot['itemposition'] ?? -1) !== (int)$item->position
            || strtoupper((string)($snapshot['currency'] ?? '')) !== strtoupper((string)$item->currency)
            || (int)$purchase->totalminor !== (int)$item->netminor
            || (int)$item->netminor <= 0) {
            throw new \DomainException('Gift immutable payment snapshot does not match.');
        }
        $metadata = json_decode((string)$item->metadatajson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($metadata) || !isset($metadata[CommerceGiftIntentSeal::METADATA_FIELD])
            || !is_array($metadata[CommerceGiftIntentSeal::METADATA_FIELD])
            || (int)($metadata['priceid'] ?? 0) !== (int)$gift->sourcepriceid) {
            throw new \DomainException('Gift payment intent is missing.');
        }
        $key = trim((string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'));
        $intent = CommerceGiftIntentSeal::verify($purchase, $item,
            $metadata[CommerceGiftIntentSeal::METADATA_FIELD], (int)$gift->sourcepriceid, $key);
        if ($intent['deliverymode'] !== (string)$gift->deliverymode
            || $intent['recipientemail'] !== $gift->intendedrecipientemail
            || $intent['scheduledat'] !== ($gift->scheduledat === null ? null : (int)$gift->scheduledat)
            || $intent['expiresat'] !== ($gift->expiresat === null ? null : (int)$gift->expiresat)) {
            throw new \DomainException('Gift stored recipient no longer matches the paid intent.');
        }
        if (!hash_equals((string)$snapshot['intentdigest'],
            hash('sha256', json_encode($intent, JSON_THROW_ON_ERROR)))) {
            throw new \DomainException('Gift payment intent has changed.');
        }
        $payment = $this->db->get_record(CommercePersistenceSchema::TABLE_PAYMENT,
            ['id' => (int)$snapshot['paymentid'], 'purchaseid' => (int)$purchase->id], '*', MUST_EXIST);
        if (!in_array((string)$payment->status, ['paid', 'completed'], true)
            || (int)($payment->paidat ?? 0) <= 0
            || (int)$payment->amountminor !== (int)$purchase->totalminor
            || strtoupper((string)$payment->currency) !== strtoupper((string)$purchase->currency)) {
            throw new \DomainException('Gift payment was not settled.');
        }
        CommerceGiftNativeProviderGuard::assert_paid((string)$purchase->currency,
            (string)$item->currency, (string)$payment->currency, (string)$payment->provider);
        foreach ($this->db->get_records(CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => (int)$purchase->id]) as $otherpayment) {
            if ((string)$otherpayment->status === 'refunded'
                || ((int)$otherpayment->id !== (int)$payment->id
                    && in_array((string)$otherpayment->status, ['paid', 'completed'], true))) {
                throw new \DomainException('Gift purchase contains another settled or refunded payment.');
            }
        }
        foreach ($this->db->get_records(CommercePersistenceSchema::TABLE_REFUND,
            ['paymentid' => (int)$payment->id]) as $refund) {
            if (!in_array((string)$refund->status, ['failed', 'error', 'cancelled'], true)) {
                throw new \DomainException('Gift payment has a pending or completed refund.');
            }
        }
        if ($this->db->record_exists('local_subs_commerce_grant',
            ['purchasereference' => (string)$purchase->reference])) {
            throw new \DomainException('Gift original purchase already granted rights.');
        }
    }
}
