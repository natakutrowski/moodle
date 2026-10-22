<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartOperationResult;
use local_subscriptions\commerce\cart\service\CommerceCartService;

/**
 * Adds a primary product and an optional Donation as two real cart lines.
 *
 * The pair is intentionally routed through the normal cart rather than Direct
 * Purchase because Direct Purchase is a one-line flow. If the Donation cannot
 * be added, a newly-added primary line is rolled back so one click never leaves
 * a half-created "course + contribution" intent behind.
 */
final class CommerceDonationCompanionCartService {
    public function __construct(private readonly CommerceCartService $cart) {
    }

    public function add_pair(
        int $customerid,
        string $currency,
        string $language,
        string $primarysku,
        int $primarypriceid,
        string $donationsku,
        int $donationpriceid,
        int $donationunits,
        array $primarymetadata = [],
        array $donationmetadata = []
    ): CommerceCartOperationResult {
        $primary = $this->cart->add_product(
            $customerid,
            $currency,
            $language,
            $primarysku,
            $primarypriceid,
            1,
            $primarymetadata
        );
        if (!$primary->has_changed()) {
            return $primary;
        }

        $donationmetadata[CommerceDonationAmountPolicy::METADATA_UNITS] = $donationunits;
        $donation = $this->cart->add_product(
            $customerid,
            $currency,
            $language,
            $donationsku,
            $donationpriceid,
            1,
            $donationmetadata
        );
        if ($donation->has_changed()) {
            return $donation;
        }

        $rollback = $this->cart->remove_product(
            $customerid,
            $currency,
            $primarysku,
            $primarypriceid
        );
        return new CommerceCartOperationResult(
            $rollback->get_cart(),
            false,
            $donation->get_messages()
        );
    }
}
