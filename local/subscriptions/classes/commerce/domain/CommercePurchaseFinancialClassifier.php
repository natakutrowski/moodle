<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Determines the financial nature of a Commerce purchase.
 *
 * The classifier is deliberately conservative: when no explicit historical
 * signal explains a zero amount, the purchase remains unclassified.
 */
final class CommercePurchaseFinancialClassifier {

    public function classify(
        CommercePurchase $purchase
    ): string {
        $payment = $purchase->get_payment();

        if ($payment->get_amount_minor() > 0) {
            return CommercePurchaseFinancialNature::PAID;
        }

        if (
            $this->metadata_boolean(
                $purchase,
                'is_trial'
            )
        ) {
            return CommercePurchaseFinancialNature::TRIAL;
        }

        if (
            $this->metadata_boolean(
                $purchase,
                'is_free_product'
            )
        ) {
            return CommercePurchaseFinancialNature::FREE_PRODUCT;
        }

        if (
            $this->has_full_discount(
                $purchase
            )
        ) {
            return CommercePurchaseFinancialNature::FULL_DISCOUNT;
        }

        if (
            $this->is_complimentary(
                $purchase
            )
        ) {
            return CommercePurchaseFinancialNature::COMPLIMENTARY;
        }

        return CommercePurchaseFinancialNature::ZERO_AMOUNT_UNCLASSIFIED;
    }

    public function is_legitimate_zero_amount(
        CommercePurchase $purchase
    ): bool {
        return CommercePurchaseFinancialNature::
            is_legitimate_zero_amount(
                $this->classify($purchase)
            );
    }

    private function has_full_discount(
        CommercePurchase $purchase
    ): bool {
        $percent = (float)$purchase->get_metadata_value(
            'discount_percent',
            $purchase->get_metadata_value(
                'locked_discount_percent',
                0
            )
        );

        if ($percent >= 100) {
            return true;
        }

        $listprice = (float)$purchase->get_metadata_value(
            'list_price',
            $purchase->get_metadata_value(
                'locked_list_price',
                0
            )
        );

        $discountamount = (float)$purchase->get_metadata_value(
            'discount_amount',
            $purchase->get_metadata_value(
                'locked_discount_amount',
                0
            )
        );

        return $listprice > 0
            && $discountamount >= $listprice;
    }

    private function is_complimentary(
        CommercePurchase $purchase
    ): bool {
        if (
            $this->metadata_boolean(
                $purchase,
                'is_complimentary'
            )
        ) {
            return true;
        }

        $reason = strtolower(
            trim(
                (string)$purchase->get_metadata_value(
                    'discount_reason',
                    $purchase->get_metadata_value(
                        'locked_discount_reason',
                        ''
                    )
                )
            )
        );

        if ($reason === '') {
            return false;
        }

        $knownmarkers = [
            'complimentary',
            'complimentaire',
            'offert',
            'gift',
            'geste commercial',
            'manual grant',
            'manual_grant',
            'admin grant',
            'admin_grant',
        ];

        foreach ($knownmarkers as $marker) {
            if (str_contains($reason, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function metadata_boolean(
        CommercePurchase $purchase,
        string $key
    ): bool {
        return filter_var(
            $purchase->get_metadata_value(
                $key,
                false
            ),
            FILTER_VALIDATE_BOOL
        );
    }
}