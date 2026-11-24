<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartItem;

/**
 * Proposed identity contract for future Gift cart lines. Not wired to the cart.
 * Ordinary lines preserve SKU:priceid exactly; each gift has its own 32-hex ID.
 */
final class CommerceGiftLineIdentity {
    public static function personal(CommerceCartItem $item): string {
        return $item->get_key();
    }

    public static function gift(CommerceCartItem $item, string $lineuuid): string {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $lineuuid)) {
            throw new \InvalidArgumentException('Gift line identifier must be 32 lowercase hexadecimal characters.');
        }
        return $item->get_key() . ':gift:' . $lineuuid;
    }
}
