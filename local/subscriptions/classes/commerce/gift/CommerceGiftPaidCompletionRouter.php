<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G3B classifier: signed intent is only a routing marker, never proof of payment.
 * The G3A service re-checks Native purchase + item + payment + signature under lock.
 */
final class CommerceGiftPaidCompletionRouter {
    /** @param \stdClass[] $items */
    public static function gift_item_id(array $items): ?int {
        $candidates = [];
        foreach ($items as $item) {
            $raw = (string)($item->metadatajson ?? '');
            // Historical personal purchases can have empty or legacy metadata.
            // The gift adapter must not reinterpret those ordinary purchases.
            if (!str_contains($raw, 'campusfr_gift_v1')
                && !str_contains($raw, 'campusfr_gift_draft_v1')) {
                continue;
            }
            $metadata = json_decode($raw, true);
            if (!is_array($metadata)) {
                throw new \DomainException('Persisted gift metadata is malformed.');
            }
            if (array_key_exists(CommerceGiftIntentSeal::METADATA_FIELD, $metadata)) {
                $candidates[] = $item;
            }
            if (array_key_exists(CommerceGiftCheckoutIntentBinder::DRAFT_FIELD, $metadata)) {
                throw new \DomainException('Unsealed gift draft cannot survive checkout persistence.');
            }
        }
        if ($candidates === []) {
            return null;
        }
        if (count($items) !== 1 || count($candidates) !== 1
            || (int)$candidates[0]->quantity !== 1
            || strtoupper(trim((string)$candidates[0]->itemreference)) !== 'AVENT_2026'
            || (int)$candidates[0]->id <= 0) {
            throw new \DomainException('Mixed or multiple gift checkout lines are not enabled.');
        }
        return (int)$candidates[0]->id;
    }
}
