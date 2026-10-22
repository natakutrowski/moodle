<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;

/**
 * Browser-session memory for Donation amount intent.
 *
 * The stored value is deliberately expressed as units, never as a currency
 * amount. This lets the same intent survive currency switches while the cart
 * and checkout keep their own authoritative, frozen monetary snapshots.
 */
final class CommerceDonationIntentSession {
    private const SESSION_KEY = 'local_subscriptions_donation_intent_units';

    public function __construct(
        private readonly CommerceDonationAmountPolicy $policy = new CommerceDonationAmountPolicy()
    ) {
    }

    public function remember(string $sku, mixed $rawunits): int {
        global $SESSION;

        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            throw new \coding_exception('Donation intent requires a product SKU.');
        }
        $units = $this->policy->require_units($rawunits);
        $intents = isset($SESSION->{self::SESSION_KEY}) && is_array($SESSION->{self::SESSION_KEY})
            ? $SESSION->{self::SESSION_KEY}
            : [];
        $intents[$sku] = $units;
        $SESSION->{self::SESSION_KEY} = $intents;
        return $units;
    }

    public function recall(string $sku): ?int {
        global $SESSION;

        $sku = strtoupper(trim($sku));
        if ($sku === '' || !isset($SESSION->{self::SESSION_KEY}) || !is_array($SESSION->{self::SESSION_KEY})) {
            return null;
        }
        if (!array_key_exists($sku, $SESSION->{self::SESSION_KEY})) {
            return null;
        }
        try {
            return $this->policy->require_units($SESSION->{self::SESSION_KEY}[$sku]);
        } catch (\coding_exception) {
            unset($SESSION->{self::SESSION_KEY}[$sku]);
            return null;
        }
    }

    public function resolve(string $sku, ?CommerceCartSnapshot $snapshot = null): int {
        $sku = strtoupper(trim($sku));
        if ($snapshot !== null && $sku !== '') {
            foreach ($snapshot->get_items() as $calculateditem) {
                $item = $calculateditem->get_item();
                if ($item->get_product_sku() !== $sku) {
                    continue;
                }
                $metadata = $item->get_metadata();
                if (!array_key_exists(CommerceDonationAmountPolicy::METADATA_UNITS, $metadata)) {
                    continue;
                }
                try {
                    return $this->policy->require_units(
                        $metadata[CommerceDonationAmountPolicy::METADATA_UNITS]
                    );
                } catch (\coding_exception) {
                    // A malformed persisted line must not poison the storefront.
                }
            }
        }

        return $this->recall($sku) ?? $this->policy->default_units();
    }
}
