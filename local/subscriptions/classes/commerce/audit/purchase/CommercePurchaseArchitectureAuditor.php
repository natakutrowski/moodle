<?php

namespace local_subscriptions\commerce\audit\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Audits the Commerce Purchase Architecture without writing data.
 */
final class CommercePurchaseArchitectureAuditor {

    public function audit(
        int $limit = 0
    ): CommercePurchaseArchitectureAuditReport {
        global $DB;

        $report =
            new CommercePurchaseArchitectureAuditReport();

        $runtime =
            CommerceRuntimeFactory::create();

        foreach (
            $runtime->purchase_handlers()->keys()
            as $key
        ) {
            $report->increment(
                'registered_purchase_handlers'
            );

            $report->increment(
                'purchase_handler_' . $key
            );
        }

        foreach (
            $runtime->fulfillment_handlers()->keys()
            as $key
        ) {
            $report->increment(
                'registered_fulfillment_handlers'
            );

            $report->increment(
                'fulfillment_handler_' . $key
            );
        }

        $plans = $DB->get_records(
            'subscription_plan',
            null,
            'id ASC',
            '*',
            0,
            $limit > 0 ? $limit : 0
        );

        foreach ($plans as $plan) {
            $report->increment(
                'subscription_plans_checked'
            );

            try {
                $item =
                    new CommercePurchaseRequestItem(
                        new CommerceItem(
                            CommerceItem::TYPE_SUBSCRIPTION,
                            'subscription-plan:' .
                                (int)$plan->id,
                            $this->resolve_name(
                                $plan,
                                'Subscription plan'
                            ),
                            (int)$plan->id
                        ),
                        1,
                        0,
                        'EUR'
                    );

                $handler =
                    $runtime
                        ->purchase_handlers()
                        ->resolve($item);

                $validation = $handler->validate(
                    $item,
                    $this->create_audit_customer()
                );

                if (!$validation->is_valid()) {
                    $report->add_warning(
                        'subscription_plan_not_purchasable',
                        'A subscription plan cannot currently be prepared.',
                        [
                            'planid' =>
                                (int)$plan->id,

                            'issues' =>
                                $validation->to_array(),
                        ]
                    );

                    continue;
                }

                $handler->prepare(
                    $item,
                    $this->create_audit_customer()
                );

                $report->increment(
                    'subscription_plans_prepared'
                );
            } catch (\Throwable $exception) {
                $report->add_error(
                    'subscription_plan_preparation_failed',
                    $exception->getMessage(),
                    [
                        'planid' =>
                            (int)$plan->id,

                        'exception' =>
                            get_class($exception),
                    ]
                );
            }
        }

        $products = $DB->get_records(
            'subscription_digital_product',
            null,
            'id ASC',
            '*',
            0,
            $limit > 0 ? $limit : 0
        );

        foreach ($products as $product) {
            $report->increment(
                'digital_products_checked'
            );

            try {
                $item =
                    new CommercePurchaseRequestItem(
                        new CommerceItem(
                            CommerceItem::TYPE_DIGITAL,
                            'digital-product:' .
                                (int)$product->id,
                            $this->resolve_name(
                                $product,
                                'Digital product'
                            ),
                            (int)$product->id
                        ),
                        1,
                        0,
                        'EUR'
                    );

                $handler =
                    $runtime
                        ->purchase_handlers()
                        ->resolve($item);

                $validation = $handler->validate(
                    $item,
                    $this->create_audit_customer()
                );

                if (!$validation->is_valid()) {
                    $report->add_warning(
                        'digital_product_not_purchasable',
                        'A digital product cannot currently be prepared.',
                        [
                            'productid' =>
                                (int)$product->id,

                            'issues' =>
                                $validation->to_array(),
                        ]
                    );

                    continue;
                }

                $handler->prepare(
                    $item,
                    $this->create_audit_customer()
                );

                $report->increment(
                    'digital_products_prepared'
                );
            } catch (\Throwable $exception) {
                $report->add_error(
                    'digital_product_preparation_failed',
                    $exception->getMessage(),
                    [
                        'productid' =>
                            (int)$product->id,

                        'exception' =>
                            get_class($exception),
                    ]
                );
            }
        }

        $this->audit_sample_bundle(
            $report
        );

        return $report;
    }

    private function audit_sample_bundle(
        CommercePurchaseArchitectureAuditReport $report
    ): void {
        global $DB;

        $plan = $DB->get_record_sql(
            "
                SELECT *
                  FROM {subscription_plan}
              ORDER BY id ASC
            ",
            [],
            IGNORE_MULTIPLE
        );

        $product = $DB->get_record_sql(
            "
                SELECT *
                  FROM {subscription_digital_product}
              ORDER BY id ASC
            ",
            [],
            IGNORE_MULTIPLE
        );

        if (!$plan || !$product) {
            $report->add_warning(
                'sample_bundle_not_available',
                'A mixed subscription and digital bundle could not be audited because one product family is empty.'
            );

            return;
        }

        try {
            $request =
                new CommercePurchaseRequest(
                    'purchase-request:audit-sample',
                    $this->create_audit_customer(),
                    [
                        new CommercePurchaseRequestItem(
                            new CommerceItem(
                                CommerceItem::TYPE_SUBSCRIPTION,
                                'subscription-plan:' .
                                    (int)$plan->id,
                                $this->resolve_name(
                                    $plan,
                                    'Subscription plan'
                                ),
                                (int)$plan->id
                            ),
                            1,
                            0,
                            'EUR'
                        ),

                        new CommercePurchaseRequestItem(
                            new CommerceItem(
                                CommerceItem::TYPE_DIGITAL,
                                'digital-product:' .
                                    (int)$product->id,
                                $this->resolve_name(
                                    $product,
                                    'Digital product'
                                ),
                                (int)$product->id
                            ),
                            1,
                            0,
                            'EUR'
                        ),
                    ]
                );

            $runtime =
                CommerceRuntimeFactory::create();

            $preparation =
                $runtime
                    ->purchase_preparation()
                    ->prepare($request);

            $operations =
                $runtime
                    ->fulfillment()
                    ->plan($preparation);

            $report->increment(
                'sample_bundle_prepared'
            );

            $report->increment(
                'sample_fulfillment_operations',
                count($operations)
            );
        } catch (\Throwable $exception) {
            $report->add_warning(
                'sample_bundle_preparation_failed',
                $exception->getMessage(),
                [
                    'exception' =>
                        get_class($exception),
                ]
            );
        }
    }

    private function create_audit_customer():
        CommerceCustomer {
        return new CommerceCustomer(
            null,
            'commerce-audit@example.com'
        );
    }

    private function resolve_name(
        \stdClass $record,
        string $fallback
    ): string {
        foreach (
            [
                'name',
                'title',
            ] as $field
        ) {
            if (
                isset($record->{$field})
                && trim((string)$record->{$field}) !== ''
            ) {
                return trim(
                    (string)$record->{$field}
                );
            }
        }

        return $fallback . ' #' .
            (int)$record->id;
    }
}