<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Read-only gift recipient summary from a VERIFIED persisted Native checkout intent. */
final class CommerceGiftPurchaseSummary {
    public function __construct(private readonly \moodle_database $db) {}

    /** @return array{recipientname:string,recipientemail:string,label:string}|null */
    public function for_purchase(int $purchaseid): ?array {
        if ($purchaseid <= 0) { return null; }
        $purchase = $this->db->get_record(CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $purchaseid], '*', IGNORE_MISSING);
        if (!$purchase) { return null; }
        $items = array_values($this->db->get_records(CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => $purchaseid]));
        if (count($items) !== 1) { return null; }
        $item = $items[0];
        if (strtoupper(trim((string)$item->itemreference)) !== 'AVENT_2026'
            || (int)$item->quantity !== 1) { return null; }
        $metadata = json_decode((string)$item->metadatajson, true);
        if (!is_array($metadata) || !is_array($metadata[CommerceGiftIntentSeal::METADATA_FIELD] ?? null)
            || (int)($metadata['priceid'] ?? 0) <= 0) { return null; }
        $key = (string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex');
        if (preg_match('/\A[a-f0-9]{64}\z/D', $key) !== 1) { return null; }
        try {
            $intent = CommerceGiftIntentSeal::verify($purchase, $item,
                $metadata[CommerceGiftIntentSeal::METADATA_FIELD], (int)$metadata['priceid'], $key);
        } catch (\Throwable $e) {
            return null; // Never present unverified browser / order metadata as gift identity.
        }
        $email = strtolower(trim((string)($intent['recipientemail'] ?? '')));
        if ($intent['deliverymode'] !== 'email' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $name = '';
        $draft = $this->db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
            ['lineuuid' => (string)$intent['lineuuid']], '*', IGNORE_MISSING);
        if ($draft && (int)$draft->buyeruserid === (int)$purchase->userid
            && (int)$draft->priceid === (int)$metadata['priceid']
            && hash_equals(strtolower(trim((string)$draft->buyeremail)),
                strtolower(trim((string)$purchase->customeremail)))
            && hash_equals(strtolower(trim((string)$draft->recipientemail)), $email)) {
            $name = trim((string)$draft->recipientname);
        }
        // Name is display-only: email is the immutable HMAC-sealed destination.
        $name = mb_substr(strip_tags($name), 0, 120);
        return ['recipientname' => $name, 'recipientemail' => $email,
            'label' => $name === '' ? $email : $name . ' (' . $email . ')'];
    }
}
