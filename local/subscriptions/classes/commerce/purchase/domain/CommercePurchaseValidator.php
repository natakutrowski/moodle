<?php

namespace local_subscriptions\commerce\purchase\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseType;
use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\CommercePurchaseCustomer;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialData;
use local_subscriptions\commerce\domain\CommercePurchaseIdentity;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;

/**
 * Validates a Commerce purchase domain object.
 *
 * Validation is non-destructive and does not persist or modify any data.
 */
final class CommercePurchaseValidator {

    /**
     * Validate a Commerce purchase.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return CommercePurchaseValidationResult
     */
    public function validate(
        CommercePurchase $purchase
    ): CommercePurchaseValidationResult {
        $result =
            CommercePurchaseValidationResult::valid();

        $this->validate_type(
            $purchase,
            $result
        );

        $this->validate_identity(
            $purchase,
            $result
        );

        $this->validate_reference(
            $purchase,
            $result
        );

        $this->validate_customer(
            $purchase,
            $result
        );

        $this->validate_item(
            $purchase,
            $result
        );

        $this->validate_payment(
            $purchase,
            $result
        );

        $this->validate_financial_data(
            $purchase,
            $result
        );

        $this->validate_status(
            $purchase,
            $result
        );

        $this->validate_timestamps(
            $purchase,
            $result
        );

        return $result;
    }

    /**
     * Assert that a Commerce purchase is valid.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return void
     */
    public function assert_valid(
        CommercePurchase $purchase
    ): void {
        $result =
            $this->validate(
                $purchase
            );

        if ($result->is_valid()) {
            return;
        }

        throw new \coding_exception(
            'Invalid Commerce purchase: '
                . json_encode(
                    $result->to_array(),
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
        );
    }

    /**
     * Validate the Commerce purchase type.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_type(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        if (
            CommercePurchaseType::is_valid(
                $purchase->get_type()
            )
        ) {
            return;
        }

        $result->add(
            'purchase_type_invalid',
            'The Commerce purchase type is not supported.',
            [
                'type' =>
                    $purchase->get_type(),
            ]
        );
    }

    /**
     * Validate the stable purchase identity.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_identity(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        try {
            $identity =
                CommercePurchaseIdentity::from_purchase(
                    $purchase
                );

            if ($identity->get_legacy_id() <= 0) {
                $result->add(
                    'purchase_identity_invalid',
                    'The Commerce purchase Legacy identity is invalid.'
                );
            }

            if (
                trim(
                    $identity->get_public_reference()
                ) === ''
            ) {
                $result->add(
                    'purchase_public_reference_missing',
                    'The Commerce purchase public reference is missing.'
                );
            }
        } catch (\Throwable $exception) {
            $result->add(
                'purchase_identity_invalid',
                'The Commerce purchase identity could not be resolved.',
                [
                    'exception' =>
                        get_class($exception),

                    'message' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Validate the purchase reference.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_reference(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        if (
            trim(
                $purchase->get_reference()
            ) !== ''
        ) {
            return;
        }

        $result->add(
            'purchase_reference_missing',
            'The Commerce purchase reference is missing.'
        );
    }

    /**
     * Validate the customer.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_customer(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        try {
            $customer =
                CommercePurchaseCustomer::from_purchase(
                    $purchase
                );

            if (!$customer->has_identity()) {
                $result->add(
                    'purchase_customer_missing',
                    'The Commerce purchase has neither a user identifier nor an email address.'
                );
            }
        } catch (\Throwable $exception) {
            $result->add(
                'purchase_customer_invalid',
                'The Commerce purchase customer is invalid.',
                [
                    'exception' =>
                        get_class($exception),

                    'message' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Validate the purchased item.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_item(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        $item =
            $purchase->get_item();

        if (
            trim(
                $item->get_reference()
            ) === ''
        ) {
            $result->add(
                'purchase_item_reference_missing',
                'The Commerce purchase item reference is missing.'
            );
        }

        if (
            trim(
                $item->get_name()
            ) === ''
        ) {
            $result->add(
                'purchase_item_name_missing',
                'The Commerce purchase item name is missing.'
            );
        }

        if (
            $item->get_type()
            !== $purchase->get_type()
        ) {
            $result->add(
                'purchase_item_type_mismatch',
                'The Commerce purchase type does not match the purchased item type.',
                [
                    'purchase_type' =>
                        $purchase->get_type(),

                    'item_type' =>
                        $item->get_type(),
                ]
            );
        }

        $legacyitemid =
            $item->get_legacy_id();

        if (
            $legacyitemid !== null
            && $legacyitemid <= 0
        ) {
            $result->add(
                'purchase_item_legacy_id_invalid',
                'The Commerce purchase item Legacy identifier is invalid.',
                [
                    'legacyid' =>
                        $legacyitemid,
                ]
            );
        }
    }

    /**
     * Validate the payment.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_payment(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        $payment =
            $purchase->get_payment();

        if ($payment->get_amount_minor() < 0) {
            $result->add(
                'purchase_payment_amount_invalid',
                'The Commerce purchase payment amount cannot be negative.'
            );
        }

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                $payment->get_currency()
            )
        ) {
            $result->add(
                'purchase_payment_currency_invalid',
                'The Commerce purchase payment currency is invalid.',
                [
                    'currency' =>
                        $payment->get_currency(),
                ]
            );
        }

        if (
            $payment->get_legacy_request_id() !== null
            && $payment->get_legacy_request_id() <= 0
        ) {
            $result->add(
                'purchase_payment_request_id_invalid',
                'The Legacy payment request identifier is invalid.',
                [
                    'legacyrequestid' =>
                        $payment->get_legacy_request_id(),
                ]
            );
        }
    }

    /**
     * Validate the financial model.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_financial_data(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        try {
            $financialdata =
                CommercePurchaseFinancialData::from_purchase(
                    $purchase
                );

            if (!$financialdata->is_consistent()) {
                $result->add(
                    'purchase_financial_data_inconsistent',
                    'The Commerce purchase financial data is inconsistent.',
                    $financialdata->to_array()
                );
            }

            if (
                $financialdata->get_currency()
                !== $purchase
                    ->get_payment()
                    ->get_currency()
            ) {
                $result->add(
                    'purchase_financial_currency_mismatch',
                    'The Commerce financial currency does not match the payment currency.',
                    [
                        'financial_currency' =>
                            $financialdata->get_currency(),

                        'payment_currency' =>
                            $purchase
                                ->get_payment()
                                ->get_currency(),
                    ]
                );
            }
        } catch (\Throwable $exception) {
            $result->add(
                'purchase_financial_data_invalid',
                'The Commerce purchase financial data could not be built.',
                [
                    'exception' =>
                        get_class($exception),

                    'message' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Validate the lifecycle status.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_status(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        $status =
            CommercePurchaseStatus::normalise(
                $purchase->get_status(),
                $purchase->get_payment()
            );

        if ($status === CommercePurchaseStatus::UNKNOWN) {
            $result->add(
                'purchase_status_unknown',
                'The Commerce purchase status could not be normalised.',
                [
                    'status' =>
                        $purchase->get_status(),

                    'paymentstatus' =>
                        $purchase
                            ->get_payment()
                            ->get_status(),
                ]
            );
        }

        if (
            $status === CommercePurchaseStatus::FULFILLED
            && !$purchase->get_payment()->is_successful()
        ) {
            $result->add(
                'purchase_fulfilled_without_successful_payment',
                'The Commerce purchase is fulfilled but its payment is not successful.',
                [
                    'status' =>
                        $purchase->get_status(),

                    'paymentstatus' =>
                        $purchase
                            ->get_payment()
                            ->get_status(),
                ]
            );
        }

        if (
            $purchase->get_payment()->is_successful()
            && in_array(
                $status,
                [
                    CommercePurchaseStatus::FAILED,
                    CommercePurchaseStatus::CANCELLED,
                    CommercePurchaseStatus::PAYMENT_PENDING,
                ],
                true
            )
        ) {
            $result->add(
                'purchase_status_payment_mismatch',
                'The Commerce purchase lifecycle status conflicts with its successful payment.',
                [
                    'status' =>
                        $status,

                    'paymentstatus' =>
                        $purchase
                            ->get_payment()
                            ->get_status(),
                ]
            );
        }
    }

    /**
     * Validate purchase timestamps.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param CommercePurchaseValidationResult $result Validation result.
     * @return void
     */
    private function validate_timestamps(
        CommercePurchase $purchase,
        CommercePurchaseValidationResult $result
    ): void {
        $createdat =
            $purchase->get_created_at();

        $updatedat =
            $purchase->get_updated_at();

        if (
            $createdat !== null
            && $createdat <= 0
        ) {
            $result->add(
                'purchase_created_at_invalid',
                'The Commerce purchase creation timestamp is invalid.',
                [
                    'createdat' =>
                        $createdat,
                ]
            );
        }

        if (
            $updatedat !== null
            && $updatedat <= 0
        ) {
            $result->add(
                'purchase_updated_at_invalid',
                'The Commerce purchase update timestamp is invalid.',
                [
                    'updatedat' =>
                        $updatedat,
                ]
            );
        }

        if (
            $createdat !== null
            && $updatedat !== null
            && $updatedat < $createdat
        ) {
            $result->add(
                'purchase_timestamp_order_invalid',
                'The Commerce purchase update timestamp precedes its creation timestamp.',
                [
                    'createdat' =>
                        $createdat,

                    'updatedat' =>
                        $updatedat,
                ]
            );
        }
    }
}