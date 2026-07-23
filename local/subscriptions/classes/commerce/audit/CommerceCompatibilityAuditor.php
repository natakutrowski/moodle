<?php

namespace local_subscriptions\commerce\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\CommercePurchaseType;
use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialClassifier;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialNature;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Audits whether historical purchases can be represented by the
 * new Commerce domain.
 *
 * This auditor is strictly read-only.
 */
final class CommerceCompatibilityAuditor {

    public function __construct(
        private readonly ?CommercePurchaseService $purchaseservice = null
    ) {
    }

    public function audit(
        int $batchsize = 200
    ): CommerceCompatibilityReport {
        global $DB;

        $batchsize = max(
            1,
            min(1000, $batchsize)
        );

        $report = new CommerceCompatibilityReport();

        $report->set_counter(
            'legacy_subscriptions_total',
            $DB->count_records('user_subscription')
        );

        $report->set_counter(
            'legacy_digital_purchases_total',
            $DB->count_records(
                'subscription_digital_payment_request'
            )
        );

        $this->audit_subscriptions(
            $report,
            $batchsize
        );

        $this->audit_digital_purchases(
            $report,
            $batchsize
        );

        return $report;
    }

    private function audit_subscriptions(
        CommerceCompatibilityReport $report,
        int $batchsize
    ): void {
        global $DB;

        $service = $this->purchaseservice
            ?? CommerceRuntimeFactory::create()
                ->purchases();

        $offset = 0;

        do {
            $records = $DB->get_records(
                'user_subscription',
                [],
                'id ASC',
                'id',
                $offset,
                $batchsize
            );

            foreach ($records as $record) {
                $subscriptionid = (int)$record->id;

                $report->increment(
                    'subscriptions_checked'
                );

                try {
                    $purchase = $service->get_purchase(
                        CommercePurchaseType::SUBSCRIPTION,
                        $subscriptionid
                    );

                    if (!$purchase instanceof CommercePurchase) {
                        $report->add_error(
                            'subscription_not_hydrated',
                            'The historical subscription could not be represented as a Commerce purchase.',
                            [
                                'subscriptionid' => $subscriptionid,
                            ]
                        );

                        continue;
                    }

                    $report->increment(
                        'subscriptions_hydrated'
                    );

                    $this->audit_common_purchase(
                        $purchase,
                        $report,
                        CommercePurchaseType::SUBSCRIPTION,
                        $subscriptionid
                    );
                } catch (\Throwable $exception) {
                    $report->add_error(
                        'subscription_hydration_exception',
                        $exception->getMessage(),
                        [
                            'subscriptionid' => $subscriptionid,
                            'exception' => get_class($exception),
                        ]
                    );
                }
            }

            $count = count($records);
            $offset += $count;
        } while ($count === $batchsize);
    }

    private function audit_digital_purchases(
        CommerceCompatibilityReport $report,
        int $batchsize
    ): void {
        global $DB;

        $service = $this->purchaseservice
            ?? CommerceRuntimeFactory::create()
                ->purchases();

        $offset = 0;

        do {
            $records = $DB->get_records(
                'subscription_digital_payment_request',
                [],
                'id ASC',
                'id',
                $offset,
                $batchsize
            );

            foreach ($records as $record) {
                $purchaseid = (int)$record->id;

                $report->increment(
                    'digital_purchases_checked'
                );

                try {
                    $purchase = $service->get_purchase(
                        CommercePurchaseType::DIGITAL,
                        $purchaseid
                    );

                    if (!$purchase instanceof CommercePurchase) {
                        $report->add_error(
                            'digital_purchase_not_hydrated',
                            'The historical digital purchase could not be represented as a Commerce purchase.',
                            [
                                'purchaseid' => $purchaseid,
                            ]
                        );

                        continue;
                    }

                    $report->increment(
                        'digital_purchases_hydrated'
                    );

                    $this->audit_common_purchase(
                        $purchase,
                        $report,
                        CommercePurchaseType::DIGITAL,
                        $purchaseid
                    );
                } catch (\Throwable $exception) {
                    $report->add_error(
                        'digital_purchase_hydration_exception',
                        $exception->getMessage(),
                        [
                            'purchaseid' => $purchaseid,
                            'exception' => get_class($exception),
                        ]
                    );
                }
            }

            $count = count($records);
            $offset += $count;
        } while ($count === $batchsize);
    }

    private function audit_common_purchase(
        CommercePurchase $purchase,
        CommerceCompatibilityReport $report,
        string $type,
        int $legacyid
    ): void {
        $context = [
            'type' => $type,
            'legacyid' => $legacyid,
            'reference' => $purchase->get_reference(),
        ];

        if (trim($purchase->get_reference()) === '') {
            $report->add_error(
                'empty_purchase_reference',
                'The Commerce purchase has no stable reference.',
                $context
            );
        }

        if (trim($purchase->get_item()->get_reference()) === '') {
            $report->add_error(
                'empty_item_reference',
                'The Commerce item has no stable reference.',
                $context
            );
        }

        if (trim($purchase->get_payment()->get_currency()) === '') {
            $report->add_error(
                'empty_payment_currency',
                'The Commerce payment has no currency.',
                $context
            );
        }

        if (
            $purchase->get_payment()->get_provider() === null
            && $purchase->get_payment()->is_successful()
        ) {
            $report->add_warning(
                'successful_payment_without_provider',
                'A successful historical payment has no provider identifier.',
                $context
            );
        }

        if (
            $purchase->get_payment()->get_amount_minor() === 0
            && $purchase->get_payment()->is_successful()
        ) {
            $classifier =
                new CommercePurchaseFinancialClassifier();

            $financialnature =
                $classifier->classify(
                    $purchase
                );

            $context['financialnature'] =
                $financialnature;

            if (
                CommercePurchaseFinancialNature::
                    is_legitimate_zero_amount(
                        $financialnature
                    )
            ) {
                $report->increment(
                    'legitimate_zero_amount_purchases'
                );

                return;
            }

            $report->increment(
                'unclassified_zero_amount_purchases'
            );

            $report->add_warning(
                'successful_zero_amount_unclassified',
                'A successful zero-amount historical payment could not be classified as trial, complimentary, fully discounted or free.',
                $context
            );
        }
    }
}