<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal\webhook;

use local_subscriptions\commerce\payment\provider\paypal\PayPalReturnCaptureService;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundImportService;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteIssuer;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\EventRouter;
use local_subscriptions\payment\Provider;

defined('MOODLE_INTERNAL') || die();

final class PayPalWebhookService {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommercePaymentRepository $payments,
        private readonly PayPalReturnCaptureService $capture,
        private readonly CommercePaymentRefundRepository $refunds
    ) {
    }

    public static function create(
        \moodle_database $database
    ): self {
        return new self(
            $database,
            new CommercePaymentRepository($database),
            PayPalReturnCaptureService::create(
                $database
            ),
            new CommercePaymentRefundRepository(
                $database
            )
        );
    }

    /**
     * @return string Processing result for diagnostics.
     */
    public function handle(
        PayPalVerifiedWebhook $webhook
    ): string {
        $eventtype =
            $webhook->get_event_type();

        return match ($eventtype) {
            'CHECKOUT.ORDER.APPROVED' =>
                $this->capture_from_order(
                    $this->order_id_from_order_event(
                        $webhook
                    )
                ),

            'PAYMENT.CAPTURE.COMPLETED' =>
                $this->finalize_completed_capture(
                    $webhook
                ),

            'PAYMENT.CAPTURE.PENDING' =>
                'acknowledged_pending',

            'PAYMENT.CAPTURE.DENIED' =>
                $this->mark_capture_failed(
                    $webhook
                ),

            'PAYMENT.CAPTURE.REFUNDED' =>
                $this->sync_capture_refunds(
                    $webhook
                ),

            'PAYMENT.REFUND.PENDING' =>
                $this->update_refund_status(
                    $webhook,
                    'pending'
                ),

            'PAYMENT.REFUND.FAILED' =>
                $this->update_refund_status(
                    $webhook,
                    'failed'
                ),

            'PAYMENT.CAPTURE.REVERSED',
            'CHECKOUT.PAYMENT-APPROVAL.REVERSED' =>
                'acknowledged_reversal_event',

            default =>
                'ignored_event',
        };
    }


    private function sync_capture_refunds(
        PayPalVerifiedWebhook $webhook
    ): string {
        $resource = $webhook->get_resource();

        $refundid = trim(
            (string)($resource['id'] ?? '')
        );
        $currency = strtoupper(
            trim(
                (string)(
                    $resource['amount']
                        ['currency_code']
                    ?? ''
                )
            )
        );
        $value = trim(
            (string)(
                $resource['amount']['value']
                ?? ''
            )
        );

        $captureid =
            $this->capture_id_from_refund_event(
                $webhook
            );

        $attempt =
            $captureid !== ''
                ? $this->find_payment_by_capture_id(
                    $captureid
                )
                : null;

        if ($attempt === null && $refundid !== '') {
            $knownrefund =
                $this->refunds
                    ->find_by_provider_refund_id(
                        Provider::PAYPAL,
                        $refundid
                    );

            if ($knownrefund !== null) {
                $attempt =
                    $this->payments->find(
                        $knownrefund
                            ->get_payment_id()
                    );
            }
        }

        if ($attempt === null) {
            return 'unresolved_refunded_capture';
        }

        // Import the exact refund represented by the signed PayPal event.
        // This is also how refunds created outside CampusFR become visible.
        if (
            $refundid !== ''
            && $currency !== ''
            && $value !== ''
        ) {
            $amount =
                \local_subscriptions\commerce\currency\CommerceCurrencyAmount
                    ::from_major_input(
                        $value,
                        $currency
                    );

            $status = strtoupper(
                trim(
                    (string)(
                        $resource['status']
                        ?? 'COMPLETED'
                    )
                )
            );

            $result =
                new CommercePaymentRefundResult(
                    Provider::PAYPAL,
                    $refundid,
                    $status === 'COMPLETED'
                        ? CommercePaymentRefundResult::STATUS_SUCCEEDED
                        : CommercePaymentRefundResult::STATUS_PENDING,
                    $currency,
                    $amount->get_amount_minor(),
                    [
                        'paypal_refund' =>
                            $resource,
                        'paypal_webhook_event_id' =>
                            $webhook->get_event_id(),
                        'captureid' =>
                            $captureid,
                        'historical_import' =>
                            true,
                    ]
                );

            $synchronizedrefund =
                $this->refunds
                    ->import_provider_refund(
                        (int)$attempt->get_id(),
                        $result,
                        null,
                        [
                            'source' =>
                                'paypal_webhook',
                            'purchaseuuid' =>
                                $attempt
                                    ->get_purchase_uuid(),
                            'providerpaymentid' =>
                                $captureid,
                        ]
                    );

            (new CommerceCreditNoteIssuer($this->database))
                ->issue_if_eligible($synchronizedrefund);

            // Provider refund webhooks synchronize finance only. Rights are
            // revoked exclusively by an explicit administrator decision.
        }

        return 'refunds_synchronized';
    }

    private function synchronize_refunds_for_payment(
        int $paymentid
    ): string {
        $imported =
            (
                new CommercePaymentRefundImportService(
                    $this->payments,
                    $this->refunds,
                    CommerceRuntimeFactory::create()
                        ->payment_providers()
                )
            )->import_for_payment(
                $paymentid
            );

        return $imported > 0
            ? 'refunds_synchronized'
            : 'refunds_already_synchronized';
    }

    private function capture_id_from_refund_event(
        PayPalVerifiedWebhook $webhook
    ): string {
        $resource = $webhook->get_resource();

        foreach (
            [
                $resource['supplementary_data']
                    ['related_ids']
                    ['capture_id']
                    ?? null,
                $resource['supplementary_data']
                    ['related_ids']
                    ['captureId']
                    ?? null,
            ]
            as $candidate
        ) {
            $candidate = trim((string)$candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        foreach ((array)($resource['links'] ?? []) as $link) {
            if (!is_array($link)) {
                continue;
            }

            if (
                strtolower(
                    trim((string)($link['rel'] ?? ''))
                ) !== 'up'
            ) {
                continue;
            }

            $href = trim(
                (string)($link['href'] ?? '')
            );

            if (
                preg_match(
                    '~/v2/payments/captures/([^/?#]+)~',
                    $href,
                    $matches
                ) === 1
            ) {
                return rawurldecode(
                    (string)$matches[1]
                );
            }
        }

        return '';
    }

    private function update_refund_status(
        PayPalVerifiedWebhook $webhook,
        string $status
    ): string {
        $resource = $webhook->get_resource();
        $refundid = trim(
            (string)($resource['id'] ?? '')
        );

        if ($refundid === '') {
            return 'unresolved_refund';
        }

        $record =
            $this->refunds
                ->find_by_provider_refund_id(
                    Provider::PAYPAL,
                    $refundid
                );

        if ($record === null) {
            return 'unresolved_refund';
        }

        if ($status === 'failed') {
            $this->refunds->mark_failed(
                $record->get_id(),
                [
                    'paypal_webhook_event_id' =>
                        $webhook->get_event_id(),
                    'paypal_refund' => $resource,
                ]
            );

            return 'refund_marked_failed';
        }

        return 'refund_pending_acknowledged';
    }

    private function find_payment_by_capture_id(
        string $captureid
    ): ?\local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt {
        global $DB;

        $record = $DB->get_record(
            \local_subscriptions\commerce\persistence\CommercePersistenceSchema::TABLE_PAYMENT,
            [
                'provider' => Provider::PAYPAL,
                'transactionid' => trim($captureid),
            ],
            'id',
            IGNORE_MISSING
        );

        if ($record === false) {
            return null;
        }

        return $this->payments->find(
            (int)$record->id
        );
    }

    private function capture_from_order(
        string $orderid
    ): string {
        $attempt =
            $this->payments
                ->find_by_provider_reference(
                    Provider::PAYPAL,
                    $orderid
                );

        if ($attempt === null) {
            return 'unresolved_order';
        }

        $beforestatus = $attempt->get_status();

        $this->capture->capture(
            (int)$attempt->get_id(),
            $orderid
        );

        return in_array(
            $beforestatus,
            ['paid', 'completed'],
            true
        )
            ? 'confirmed_idempotently'
            : 'captured_and_finalized';
    }

    private function finalize_completed_capture(
        PayPalVerifiedWebhook $webhook
    ): string {
        $orderid =
            $this->order_id_from_capture_event(
                $webhook
            );

        if ($orderid === '') {
            return 'unresolved_capture_order';
        }

        return $this->capture_from_order(
            $orderid
        );
    }

    private function mark_capture_failed(
        PayPalVerifiedWebhook $webhook
    ): string {
        $orderid =
            $this->order_id_from_capture_event(
                $webhook
            );

        if ($orderid === '') {
            return 'unresolved_failed_capture';
        }

        $attempt =
            $this->payments
                ->find_by_provider_reference(
                    Provider::PAYPAL,
                    $orderid
                );

        if ($attempt === null) {
            return 'unresolved_failed_capture';
        }

        $resource =
            $webhook->get_resource();

        EventRouter::handle(
            new InternalEvent(
                'payment_failed',
                [
                    'currency' =>
                        strtoupper(
                            trim(
                                (string)(
                                    $resource['amount']['currency_code']
                                    ?? ''
                                )
                            )
                        ),
                    'meta' => [
                        'provider' =>
                            Provider::PAYPAL,
                        'commerce_payment_id' =>
                            (string)$attempt->get_id(),
                        'commerce_purchase_uuid' =>
                            $attempt->get_purchase_uuid(),
                        'provider_payment_id' =>
                            $orderid,
                        'session' =>
                            $orderid,
                        'paypal_order_id' =>
                            $orderid,
                        'paypal_capture_id' =>
                            trim(
                                (string)(
                                    $resource['id']
                                    ?? ''
                                )
                            ),
                        'paypal_webhook_event_id' =>
                            $webhook->get_event_id(),
                    ],
                ]
            )
        );

        return 'marked_failed';
    }

    private function order_id_from_order_event(
        PayPalVerifiedWebhook $webhook
    ): string {
        return trim(
            (string)(
                $webhook
                    ->get_resource()['id']
                ?? ''
            )
        );
    }

    private function order_id_from_capture_event(
        PayPalVerifiedWebhook $webhook
    ): string {
        $resource =
            $webhook->get_resource();

        return trim(
            (string)(
                $resource['supplementary_data']
                    ['related_ids']
                    ['order_id']
                ?? ''
            )
        );
    }
}
