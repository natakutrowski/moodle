<?php

namespace local_subscriptions\commerce\fulfillment\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\CommercePurchaseIdentity;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler;

/**
 * Inspects existing purchases without writing or sending emails.
 */
final class CommerceFulfillmentShadowService {

    public function inspect(
        CommercePurchase $purchase,
        ?int $at = null
    ): CommerceFulfillmentShadowReport {
        $identity = CommercePurchaseIdentity::from_purchase($purchase);

        if ($purchase instanceof SubscriptionPurchase) {
            $fulfilled = $purchase->is_current_at($at ?? time());
            $issues = [];

            if ($purchase->get_legacy_subscription_id() <= 0) {
                $issues[] = 'missing_legacy_subscription_id';
            }

            return new CommerceFulfillmentShadowReport(
                $identity->get_key(),
                SubscriptionEnrolmentFulfillmentHandler::KEY,
                $fulfilled,
                [
                    'subscriptionid' =>
                        $purchase->get_legacy_subscription_id(),
                    'planid' => $purchase->get_plan_id(),
                    'start_date' => $purchase->get_start_date(),
                    'end_date' => $purchase->get_end_date(),
                ],
                $issues
            );
        }

        if ($purchase instanceof DigitalPurchase) {
            $fulfilled = $purchase->has_download_access();
            $issues = [];

            if ($purchase->get_legacy_purchase_id() <= 0) {
                $issues[] = 'missing_legacy_digital_purchase_id';
            }

            return new CommerceFulfillmentShadowReport(
                $identity->get_key(),
                DigitalDownloadFulfillmentHandler::KEY,
                $fulfilled,
                [
                    'paymentrequestid' =>
                        $purchase->get_legacy_purchase_id(),
                    'productid' => $purchase->get_product_id(),
                    'download_token_expires' =>
                        $purchase->get_download_token_expires(),
                ],
                $issues
            );
        }

        return new CommerceFulfillmentShadowReport(
            $identity->get_key(),
            'unknown',
            false,
            [],
            ['unsupported_purchase_type']
        );
    }

    /**
     * @param CommercePurchase[] $purchases
     * @return CommerceFulfillmentShadowReport[]
     */
    public function inspect_many(array $purchases): array {
        return array_map(
            fn(CommercePurchase $purchase): CommerceFulfillmentShadowReport =>
                $this->inspect($purchase),
            $purchases
        );
    }
}
