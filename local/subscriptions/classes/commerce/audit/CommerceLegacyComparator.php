<?php

namespace local_subscriptions\commerce\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\legacy\DigitalPurchaseFactory;
use local_subscriptions\commerce\legacy\SubscriptionPurchaseFactory;

/**
 * Compares historical records with their new Commerce representation.
 *
 * No database write is performed.
 */
final class CommerceLegacyComparator {

    public function compare(
        int $batchsize = 200
    ): CommerceLegacyComparisonResult {
        $batchsize = max(
            1,
            min(1000, $batchsize)
        );

        $result = new CommerceLegacyComparisonResult();

        $this->compare_subscriptions(
            $result,
            $batchsize
        );

        $this->compare_digital_purchases(
            $result,
            $batchsize
        );

        return $result;
    }

    private function compare_subscriptions(
        CommerceLegacyComparisonResult $result,
        int $batchsize
    ): void {
        global $DB;

        $offset = 0;

        do {
            $subscriptions = $DB->get_records(
                'user_subscription',
                [],
                'id ASC',
                '*',
                $offset,
                $batchsize
            );

            foreach ($subscriptions as $subscription) {
                $result->increment(
                    'subscriptions_compared'
                );

                $subscriptionid = (int)$subscription->id;

                $paymentrequest =
                    $this->get_latest_subscription_payment_request(
                        $subscriptionid
                    );

                $plan = $DB->get_record(
                    'subscription_plan',
                    [
                        'id' => (int)$subscription->planid,
                    ]
                ) ?: null;

                $user = null;

                if (!empty($subscription->userid)) {
                    $user = $DB->get_record(
                        'user',
                        [
                            'id' => (int)$subscription->userid,
                        ],
                        'id,email'
                    ) ?: null;
                }

                try {
                    $purchase =
                        SubscriptionPurchaseFactory::from_legacy_records(
                            $subscription,
                            $paymentrequest,
                            $plan,
                            $user
                        );

                    $this->compare_subscription(
                        $subscription,
                        $paymentrequest,
                        $purchase,
                        $result
                    );
                } catch (\Throwable $exception) {
                    $result->add_difference(
                        'subscription',
                        $subscriptionid,
                        '__exception__',
                        null,
                        get_class($exception) .
                        ': ' .
                        $exception->getMessage()
                    );
                }
            }

            $count = count($subscriptions);
            $offset += $count;
        } while ($count === $batchsize);
    }

    private function compare_digital_purchases(
        CommerceLegacyComparisonResult $result,
        int $batchsize
    ): void {
        global $DB;

        $offset = 0;

        do {
            $records = $DB->get_records(
                'subscription_digital_payment_request',
                [],
                'id ASC',
                '*',
                $offset,
                $batchsize
            );

            foreach ($records as $record) {
                $result->increment(
                    'digital_purchases_compared'
                );

                $product = $DB->get_record(
                    'subscription_digital_product',
                    [
                        'id' => (int)$record->productid,
                    ]
                ) ?: null;

                try {
                    $purchase =
                        DigitalPurchaseFactory::from_legacy_records(
                            $record,
                            $product
                        );

                    $this->compare_digital_purchase(
                        $record,
                        $purchase,
                        $result
                    );
                } catch (\Throwable $exception) {
                    $result->add_difference(
                        'digital',
                        (int)$record->id,
                        '__exception__',
                        null,
                        get_class($exception) .
                        ': ' .
                        $exception->getMessage()
                    );
                }
            }

            $count = count($records);
            $offset += $count;
        } while ($count === $batchsize);
    }

    private function compare_subscription(
        \stdClass $subscription,
        ?\stdClass $paymentrequest,
        SubscriptionPurchase $purchase,
        CommerceLegacyComparisonResult $result
    ): void {
        $legacyid = (int)$subscription->id;

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'subscriptionid',
            $legacyid,
            $purchase->get_legacy_subscription_id()
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'planid',
            (int)$subscription->planid,
            $purchase->get_plan_id()
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'userid',
            $this->nullable_positive_int(
                $subscription->userid ?? null
            ),
            $purchase->get_user_id()
        );

        $expectedcurrency = strtoupper(
            trim(
                (string)(
                    $paymentrequest->currency
                    ?? $subscription->currency
                    ?? 'EUR'
                )
            )
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'currency',
            $expectedcurrency,
            $purchase->get_payment()->get_currency()
        );

        $expectedprovider = $this->nullable_string(
            $paymentrequest->payment_provider
            ?? $subscription->payment_provider
            ?? null
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'payment_provider',
            $expectedprovider,
            $purchase->get_payment()->get_provider()
        );

        $expectedtransaction = $this->nullable_string(
            $paymentrequest->transactionid
            ?? $subscription->transactionid
            ?? null
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'transactionid',
            $expectedtransaction,
            $purchase->get_payment()->get_transaction_id()
        );

        $expectedamountminor =
            $this->resolve_subscription_amount_minor(
                $subscription,
                $paymentrequest
            );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'amount_minor',
            $expectedamountminor,
            $purchase->get_payment()->get_amount_minor()
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'start_date',
            $this->nullable_positive_int(
                $subscription->start_date ?? null
            ),
            $purchase->get_start_date()
        );

        $this->compare_value(
            $result,
            'subscription',
            $legacyid,
            'end_date',
            $this->nullable_positive_int(
                $subscription->end_date ?? null
            ),
            $purchase->get_end_date()
        );
    }

    private function compare_digital_purchase(
        \stdClass $record,
        DigitalPurchase $purchase,
        CommerceLegacyComparisonResult $result
    ): void {
        $legacyid = (int)$record->id;

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'purchaseid',
            $legacyid,
            $purchase->get_legacy_purchase_id()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'productid',
            (int)$record->productid,
            $purchase->get_product_id()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'userid',
            $this->nullable_positive_int(
                $record->userid ?? null
            ),
            $purchase->get_user_id()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'email',
            $this->nullable_string(
                $record->email ?? null
            ),
            $purchase->get_customer_email()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'currency',
            strtoupper(
                trim(
                    (string)$record->currency
                )
            ),
            $purchase->get_payment()->get_currency()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'payment_provider',
            $this->nullable_string(
                $record->payment_provider ?? null
            ),
            $purchase->get_payment()->get_provider()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'transactionid',
            $this->nullable_string(
                $record->transactionid ?? null
            ),
            $purchase->get_payment()->get_transaction_id()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'amount_minor',
            $this->resolve_digital_amount_minor(
                $record
            ),
            $purchase->get_payment()->get_amount_minor()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'download_token',
            $this->nullable_string(
                $record->download_token ?? null
            ),
            $purchase->get_download_token()
        );

        $this->compare_value(
            $result,
            'digital',
            $legacyid,
            'download_token_expires',
            $this->nullable_positive_int(
                $record->download_token_expires ?? null
            ),
            $purchase->get_download_token_expires()
        );
    }

    private function compare_value(
        CommerceLegacyComparisonResult $result,
        string $type,
        int $legacyid,
        string $field,
        mixed $legacyvalue,
        mixed $commercevalue
    ): void {
        if ($legacyvalue === $commercevalue) {
            return;
        }

        $result->add_difference(
            $type,
            $legacyid,
            $field,
            $legacyvalue,
            $commercevalue
        );
    }

    private function get_latest_subscription_payment_request(
        int $subscriptionid
    ): ?\stdClass {
        global $DB;

        $records = $DB->get_records(
            'subscription_payment_request',
            [
                'subscriptionid' => $subscriptionid,
            ],
            'id DESC',
            '*',
            0,
            1
        );

        return $records !== []
            ? reset($records)
            : null;
    }

    private function resolve_subscription_amount_minor(
        \stdClass $subscription,
        ?\stdClass $paymentrequest
    ): int {
        if (
            $paymentrequest !== null
            && isset($paymentrequest->amount_minor)
            && (int)$paymentrequest->amount_minor > 0
        ) {
            return (int)$paymentrequest->amount_minor;
        }

        $amount = $paymentrequest->locked_final_price
            ?? $paymentrequest->price
            ?? $subscription->pricepaid
            ?? 0;

        return max(
            0,
            (int)round(
                ((float)$amount) * 100
            )
        );
    }

    private function resolve_digital_amount_minor(
        \stdClass $record
    ): int {
        if (
            isset($record->amount_minor)
            && (int)$record->amount_minor > 0
        ) {
            return (int)$record->amount_minor;
        }

        $amount = $record->locked_final_price
            ?? $record->price
            ?? 0;

        return max(
            0,
            (int)round(
                ((float)$amount) * 100
            )
        );
    }

    private function nullable_string(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string)$value
        );

        return $value === ''
            ? null
            : $value;
    }

    private function nullable_positive_int(
        mixed $value
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0
            ? $value
            : null;
    }
}