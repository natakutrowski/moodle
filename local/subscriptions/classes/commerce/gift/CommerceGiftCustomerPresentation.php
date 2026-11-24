<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** Customer-only, read-only gift presentation. Never returns tokens or secrets. */
final class CommerceGiftCustomerPresentation {
    public function __construct(private readonly \moodle_database $db) {}

    /** The caller has already proved ownership of the presentation order. */
    public function for_owned_order(\local_subscriptions\commerce\order\presentation\CommerceOrderPresentation $order): array {
        // This pilot accepts exactly one signed Avent line. Browser return flags have no authority.
        if (count($order->items) !== 1) {
            return ['isgift' => false, 'gift' => null];
        }
        $item = $order->items[0];
        $sku = strtoupper(trim((string)($item->metadata['productsku'] ?? $item->metadata['sku'] ?? $item->reference)));
        $envelope = $item->metadata[CommerceGiftIntentSeal::METADATA_FIELD] ?? null;
        if ($sku !== 'AVENT_2026' || !is_array($envelope)
            || !is_array($envelope['intent'] ?? null)
            || !preg_match('/\A[a-f0-9]{64}\z/D', (string)($envelope['seal'] ?? ''))) {
            return ['isgift' => false, 'gift' => null];
        }
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['purchaseid' => (int)$order->purchaseid, 'sourceproductsku' => 'AVENT_2026'], '*', IGNORE_MISSING);
        if ($gift && (int)$gift->purchaseid !== (int)$order->purchaseid) {
            $gift = null;
        }
        // A signed envelope alone only identifies a gift order; it never proves payment or issuance.
        return ['isgift' => true, 'gift' => $gift ?: null];
    }

    /** @return array<int,\stdClass> */
    public function offered(int $buyerid): array {
        if ($buyerid <= 2) { return []; }
        return array_values($this->db->get_records(CommerceGiftSchema::TABLE_GIFT,
            ['buyeruserid' => $buyerid, 'sourceproductsku' => 'AVENT_2026'],
            'timecreated DESC, id DESC', '*', 0, 100));
    }

    /** @return array<int,\stdClass> */
    public function received(int $userid): array {
        if ($userid <= 2) { return []; }
        $sql = 'SELECT * FROM {' . CommerceGiftSchema::TABLE_GIFT . '}' .
            ' WHERE sourceproductsku = :sku AND (rightsholderuserid = :owner1' .
            ' OR intendedrecipientuserid = :owner2) ORDER BY timecreated DESC, id DESC';
        return array_values($this->db->get_records_sql($sql,
            ['sku' => 'AVENT_2026', 'owner1' => $userid, 'owner2' => $userid], 0, 100));
    }

    /**
     * Payment summary only for the purchaser. Recipient screens must never expose prices.
     * Uses the immutable Commerce purchase total and currency, not the current catalogue price.
     * Ownership is checked again against the purchase itself (defence in depth).
     * @return array{reference:string,amountminor:int,currency:string,created:int}|null
     */
    public function receipt_for_offered(\stdClass $gift, int $buyerid): ?array {
        if ($buyerid <= 2 || (int)$gift->buyeruserid !== $buyerid || (int)$gift->purchaseid <= 0) {
            return null;
        }
        $purchase = $this->db->get_record(
            \local_subscriptions\commerce\persistence\CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => (int)$gift->purchaseid, 'userid' => $buyerid],
            'id, reference, currency, totalminor, timecreated', IGNORE_MISSING
        );
        if (!$purchase) { return null; }
        $currency = strtoupper(trim((string)$purchase->currency));
        if (!in_array($currency, ['EUR', 'RUB'], true) || (int)$purchase->totalminor < 0
            || !preg_match('/\A[a-zA-Z0-9_-]{1,28}\z/D', (string)$purchase->reference)) {
            return null;
        }
        return [
            'reference' => (string)$purchase->reference,
            'amountminor' => (int)$purchase->totalminor,
            'currency' => $currency,
            'created' => (int)$purchase->timecreated,
        ];
    }

    public static function status_key(string $status): string {
        return match ($status) {
            'active' => 'cfr_gift_g4_status_active',
            'claimed', 'grant_pending', 'grant_failed' => 'cfr_gift_g4_status_claimed',
            'cancelled', 'expired' => 'cfr_gift_g4_status_unavailable',
            'issued', 'scheduled', 'notified', 'delivery_failed' => 'cfr_gift_g4_status_issued',
            default => 'cfr_gift_g4_status_processing',
        };
    }
}
