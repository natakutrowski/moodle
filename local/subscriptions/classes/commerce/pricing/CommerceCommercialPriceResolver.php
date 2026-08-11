<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;

/** Builds the canonical commercial price chain for a calculated cart line. */
final class CommerceCommercialPriceResolver {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function resolve(
        object $calculated,
        string $currency,
        int $allocatedtotalminor,
        int $userid = 0
    ): CommerceCommercialPriceBreakdown {
        $item = $calculated->get_item();
        $metadata = $item->get_metadata();
        $quantity = $item->get_quantity();

        if ($quantity <= 0 || $allocatedtotalminor % $quantity !== 0) {
            throw new \RuntimeException(
                'Commercial pricing requires an indivisible unit amount.'
            );
        }

        $currency = strtoupper(trim($currency));
        $finalunit = intdiv($allocatedtotalminor, $quantity);
        $operation = strtolower(trim((string)(
            $metadata['operation'] ?? ''
        )));
        $trialpercent = max(
            0,
            min(100, (int)($metadata['trialdiscountpercent'] ?? 0))
        );

        $catalogueamount = $finalunit;
        $cataloguelist = $catalogueamount;
        $promotionpercent = 0;
        $cataloguepriceresolved = false;

        $product = CommerceStorefrontRepository::create($this->db)->find_by_sku(
            $item->get_product_sku(),
            current_language(),
            $currency,
            true
        );

        if ($product !== null) {
            foreach ($product->get_prices() as $price) {
                if (
                    (int)$price->get_id() !== $item->get_price_id()
                    || $price->get_currency() !== $currency
                ) {
                    continue;
                }

                $catalogueamount = $price->get_amount_minor();
                $cataloguelist =
                    $price->get_compare_amount_minor()
                    ?? $catalogueamount;
                $promotionpercent =
                    (int)($price->get_discount_percentage() ?? 0);
                $cataloguepriceresolved = true;
                break;
            }
        }

        // Trial cart lines are already locked at their final discounted
        // amount. In isolated tests, recovery flows, or historical orders,
        // the catalogue price may no longer be resolvable. Reconstruct the
        // pre-Trial amount from the persisted percentage so the canonical
        // breakdown remains internally consistent without changing the
        // authoritative final amount.
        if (
            !$cataloguepriceresolved
            && $operation === 'trialconversion'
            && $trialpercent > 0
            && $trialpercent < 100
        ) {
            $catalogueamount = (int)round(
                $finalunit * 100 / (100 - $trialpercent)
            );
            $cataloguelist = $catalogueamount;
        }

        $promotionunit = max(
            0,
            $cataloguelist - $catalogueamount
        );

        $trialadjustedunit = $trialpercent > 0 && $trialpercent < 100
            ? max(
                0,
                $catalogueamount
                - intdiv(
                    ($catalogueamount * $trialpercent) + 50,
                    100
                )
            )
            : $catalogueamount;

        $trialunit = max(
            0,
            $catalogueamount - $trialadjustedunit
        );

        $fallbackcredit = $operation === 'upgrade'
            ? max(0, $trialadjustedunit - $finalunit)
            : 0;

        $ownedcreditunit = $operation === 'upgrade'
            ? (
                new CommerceOwnedProductCreditResolver($this->db)
            )->resolve(
                $userid,
                (int)($metadata['sourceplanid'] ?? 0),
                $currency,
                $trialpercent,
                $fallbackcredit
            )
            : 0;

        $adjustmentdiscountunit = 0;
        if ($operation !== 'upgrade') {
            $adjustmentdiscountunit = max(0, $trialadjustedunit - $finalunit);
        }

        // The calculated cart total remains authoritative. When historical
        // source data cannot reconcile exactly, retain the locked final amount
        // and record the exact effective credit required by that total.
        if (
            $operation === 'upgrade'
            && $trialadjustedunit - $ownedcreditunit !== $finalunit
        ) {
            $ownedcreditunit = max(
                0,
                $trialadjustedunit - $finalunit
            );
        }

        return new CommerceCommercialPriceBreakdown(
            $currency,
            $quantity,
            $cataloguelist,
            $promotionunit,
            $catalogueamount,
            $trialunit,
            $trialadjustedunit,
            $ownedcreditunit,
            $finalunit,
            $promotionpercent,
            $trialpercent,
            $operation,
            isset($metadata['upgradefromlabel'])
                ? (string)$metadata['upgradefromlabel']
                : null,
            isset($metadata['upgradetolabel'])
                ? (string)$metadata['upgradetolabel']
                : null,
            $adjustmentdiscountunit
        );
    }
}
