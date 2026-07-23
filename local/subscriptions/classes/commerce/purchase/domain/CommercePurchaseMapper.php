<?php

namespace local_subscriptions\commerce\purchase\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\CommercePurchaseCustomer;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialData;
use local_subscriptions\commerce\domain\CommercePurchaseIdentity;
use local_subscriptions\commerce\domain\CommercePurchaseSource;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;

/**
 * Maps a Commerce purchase to a provider-independent array representation.
 */
final class CommercePurchaseMapper {

    /**
     * Map a Commerce purchase.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return array
     */
    public function to_array(
        CommercePurchase $purchase
    ): array {
        $identity = CommercePurchaseIdentity::from_purchase(
            $purchase
        );

        $customer = CommercePurchaseCustomer::from_purchase(
            $purchase
        );

        $financialdata =
            CommercePurchaseFinancialData::from_purchase(
                $purchase
            );

        return [
            'identity' => $identity->to_array(),

            'type' => $purchase->get_type(),

            'reference' => $purchase->get_reference(),

            'source' => CommercePurchaseSource::from_purchase(
                $purchase
            ),

            'status' => CommercePurchaseStatus::normalise(
                $purchase->get_status(),
                $purchase->get_payment()
            ),

            'legacy_status' => $purchase->get_status(),

            'customer' => $customer->to_array(),

            'item' => $this->map_item($purchase),

            'financial' => $financialdata->to_array(),

            'payment' => $this->map_payment($purchase),

            'access' => $this->map_access($purchase),

            'timestamps' => [
                'created_at' => $purchase->get_created_at(),
                'updated_at' => $purchase->get_updated_at(),
            ],

            'metadata' => $purchase->get_metadata(),
        ];
    }

    /**
     * Map several purchases.
     *
     * @param CommercePurchase[] $purchases Purchases.
     * @return array
     */
    public function many_to_array(array $purchases): array {
        $mapped = [];

        foreach ($purchases as $purchase) {
            if (!$purchase instanceof CommercePurchase) {
                throw new \coding_exception(
                    'CommercePurchaseMapper received an invalid purchase.'
                );
            }

            $mapped[] = $this->to_array($purchase);
        }

        return $mapped;
    }

    /**
     * Map the purchased item.
     *
     * @param CommercePurchase $purchase Purchase.
     * @return array
     */
    private function map_item(
        CommercePurchase $purchase
    ): array {
        $item = $purchase->get_item();

        return [
            'type' => $item->get_type(),
            'reference' => $item->get_reference(),
            'name' => $item->get_name(),
            'legacy_id' => $item->get_legacy_id(),
            'metadata' => $item->get_metadata(),
        ];
    }

    /**
     * Map the payment.
     *
     * @param CommercePurchase $purchase Purchase.
     * @return array
     */
    private function map_payment(
        CommercePurchase $purchase
    ): array {
        $payment = $purchase->get_payment();

        return [
            'amount_minor' => $payment->get_amount_minor(),
            'currency' => $payment->get_currency(),
            'status' => $payment->get_status(),
            'successful' => $payment->is_successful(),
            'pending' => $payment->is_pending(),
            'failed' => $payment->is_failed(),
            'provider' => $payment->get_provider(),
            'transaction_id' => $payment->get_transaction_id(),
            'legacy_request_id' => $payment->get_legacy_request_id(),
            'paid_at' => $payment->get_paid_at(),
            'metadata' => $payment->get_metadata(),
        ];
    }

    /**
     * Map type-specific access information.
     *
     * @param CommercePurchase $purchase Purchase.
     * @return array
     */
    private function map_access(
        CommercePurchase $purchase
    ): array {
        if ($purchase instanceof SubscriptionPurchase) {
            return [
                'kind' => 'subscription',
                'legacy_subscription_id' =>
                    $purchase->get_legacy_subscription_id(),
                'plan_id' => $purchase->get_plan_id(),
                'start_date' => $purchase->get_start_date(),
                'end_date' => $purchase->get_end_date(),
                'currently_active' => $purchase->is_current_at(),
            ];
        }

        if ($purchase instanceof DigitalPurchase) {
            return [
                'kind' => 'digital',
                'legacy_purchase_id' =>
                    $purchase->get_legacy_purchase_id(),
                'product_id' => $purchase->get_product_id(),
                'download_token' => $purchase->get_download_token(),
                'download_token_expires' =>
                    $purchase->get_download_token_expires(),
                'download_access' => $purchase->has_download_access(),
            ];
        }

        return [
            'kind' => 'unknown',
        ];
    }
}