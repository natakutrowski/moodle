<?php

namespace local_subscriptions\commerce\purchase\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\CommercePurchaseIdentity;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseMapper;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseValidator;

/**
 * Safely evaluates Legacy-backed purchases through the Commerce domain.
 *
 * This service does not write to the database and does not execute payment,
 * checkout or fulfillment operations.
 */
final class CommercePurchaseShadowService {

    /**
     * @param CommercePurchaseValidator $validator Purchase validator.
     * @param CommercePurchaseMapper $mapper Purchase mapper.
     */
    public function __construct(
        private readonly CommercePurchaseValidator $validator,
        private readonly CommercePurchaseMapper $mapper
    ) {
    }

    /**
     * Evaluate one purchase.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return CommercePurchaseShadowReport
     */
    public function evaluate(
        CommercePurchase $purchase
    ): CommercePurchaseShadowReport {
        $purchasekey = $purchase->get_reference();
        $snapshot = [];
        $issues = [];
        $errors = [];

        try {
            $identity = CommercePurchaseIdentity::from_purchase(
                $purchase
            );

            $purchasekey = $identity->get_key();
        } catch (\Throwable $exception) {
            $errors[] = $this->map_exception(
                'identity_error',
                $exception
            );
        }

        try {
            $validation = $this->validator->validate(
                $purchase
            );

            $issues = $validation->to_array();
        } catch (\Throwable $exception) {
            $errors[] = $this->map_exception(
                'validation_error',
                $exception
            );
        }

        try {
            $snapshot = $this->mapper->to_array(
                $purchase
            );
        } catch (\Throwable $exception) {
            $errors[] = $this->map_exception(
                'mapping_error',
                $exception
            );
        }

        return new CommercePurchaseShadowReport(
            $purchasekey,
            $snapshot,
            $issues,
            $errors
        );
    }

    /**
     * Evaluate several purchases.
     *
     * @param CommercePurchase[] $purchases Purchases.
     * @return CommercePurchaseShadowReport[]
     */
    public function evaluate_many(array $purchases): array {
        $reports = [];

        foreach ($purchases as $purchase) {
            if (!$purchase instanceof CommercePurchase) {
                throw new \coding_exception(
                    'CommercePurchaseShadowService received an invalid purchase.'
                );
            }

            $reports[] = $this->evaluate($purchase);
        }

        return $reports;
    }

    /**
     * Convert an exception to a safe diagnostic structure.
     *
     * @param string $code Error code.
     * @param \Throwable $exception Exception.
     * @return array
     */
    private function map_exception(
        string $code,
        \Throwable $exception
    ): array {
        return [
            'code' => $code,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ];
    }
}