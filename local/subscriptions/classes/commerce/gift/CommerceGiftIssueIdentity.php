<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable idempotency identity for one unit of one paid purchase line.
 * This is NOT proof of payment and never triggers issuance by itself.
 */
final class CommerceGiftIssueIdentity {
    public static function for_paid_unit(int $purchaseid, int $itemid, int $unitindex = 1): string {
        if ($purchaseid <= 0 || $itemid <= 0 || $unitindex <= 0) {
            throw new \InvalidArgumentException('Gift issue identity requires positive identifiers.');
        }
        return hash('sha256', implode('|', [
            'campusfr-gift-issuance-v1', $purchaseid, $itemid, $unitindex,
        ]));
    }
}
