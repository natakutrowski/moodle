<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/**
 * Public, read-only Commerce fields allowed through the Lovable bridge.
 * Checkout continues to be authorised by cart_action.php, never by React.
 */
final class CommerceShowroomBridgeOfferContract {
    private const KEYS = [
        'role', 'sku', 'available', 'owned', 'canbuy', 'canaccess',
        'name', 'shortdescription', 'hasprice', 'amountminor', 'priceformatted',
        'priceid', 'currency', 'detailsurl', 'ownedactionurl', 'ownedactionlabel',
        'buylabel', 'cartaction', 'sesskey', 'returnurl', 'isdonation',
        'donationpresets', 'donationmaxunits', 'donationdefaultunits',
        'hasdonationcompanion', 'donationcompanionoptional', 'donationcompanionrequired',
        'donationcompanionmode', 'donationcompanionsku', 'donationcompanionpriceid',
        'donationcompanionrole', 'donationcompanionamountminor', 'donationcompanioncurrency',
    ];

    /** @param array<int,mixed> $offers @return array<int,array<string,mixed>> */
    public static function expose(array $offers): array {
        $result = [];
        foreach ($offers as $offer) {
            if (!is_array($offer)) {
                continue;
            }
            $result[] = array_intersect_key($offer, array_flip(self::KEYS));
        }
        return $result;
    }
}
