<?php

namespace local_subscriptions\digital\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\digital_payment_service;
use local_subscriptions\digital\dto\DigitalPaymentProviderStatus;
use local_subscriptions\digital\providers\DigitalPaymentStatusProviderRegistry;
use local_subscriptions\digital\repositories\DigitalPaymentReconciliationRepository;
use local_subscriptions\payment\dto\InternalEvent;

/**
 * Reconciles digital payment requests with live providers.
 */
final class DigitalPaymentReconciliationService {

    private DigitalPaymentReconciliationRepository $repository;

    private DigitalPaymentStatusProviderRegistry $providers;

    public function __construct(
        ?DigitalPaymentReconciliationRepository $repository = null,
        ?DigitalPaymentStatusProviderRegistry $providers = null
    ) {
        $this->repository =
            $repository ??
            new DigitalPaymentReconciliationRepository();

        $this->providers =
            $providers ??
            new DigitalPaymentStatusProviderRegistry();
    }

    public function reconcile_pending(
        array $options = []
    ): array {
        $limits =
            $this->normalize_options(
                $options
            );

        $now = time();

        $records =
            $this->repository
                ->find_pending_candidates(
                    $now - $limits['minage'],
                    $now - $limits['maxage'],
                    $limits['limit']
                );

        $result = [
            'reconciled' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($records as $paymentrequest) {
            try {
                $current =
                    $this->repository
                        ->find_by_id(
                            (int)$paymentrequest->id,
                            IGNORE_MISSING
                        );

                if (
                    !$current ||
                    strtolower(
                        (string)$current->status
                    ) !== 'pending'
                ) {
                    $result['skipped']++;
                    continue;
                }

                $providerstatus =
                    $this->providers->check(
                        $current
                    );

                if ($providerstatus->is_paid()) {
                    $this->complete_payment(
                        $current
                    );

                    $result['reconciled']++;
                    continue;
                }

                if (
                    $this->must_mark_failed(
                        $providerstatus
                    )
                ) {
                    $this->repository
                        ->mark_failed_if_pending(
                            (int)$current->id,
                            '[cron_reconcile] Provider failed/unpaid: ' .
                            $providerstatus->reason,
                            time()
                        );

                    $result['failed']++;
                    continue;
                }

                $this->repository
                    ->record_pending_provider_status(
                        (int)$current->id,
                        '[cron_reconcile] Provider status: ' .
                        $providerstatus->status .
                        (
                            $providerstatus->reason !== ''
                                ? ' - ' .
                                    $providerstatus->reason
                                : ''
                        ),
                        time()
                    );

                $result['skipped']++;
            } catch (\Throwable $exception) {
                $result['errors']++;

                $this->repository
                    ->record_reconciliation_error(
                        (int)$paymentrequest->id,
                        $this->safe_exception_message(
                            $exception
                        ),
                        time()
                    );

                debugging(
                    'Digital payment reconciliation failed for request #' .
                    (int)$paymentrequest->id .
                    '.',
                    DEBUG_DEVELOPER
                );
            }
        }

        return $result;
    }

    public function check_provider_status(
        \stdClass $paymentrequest
    ): array {
        return
            $this->providers
                ->check(
                    $paymentrequest
                )
                ->to_array();
    }

    public function check_one(
        int $paymentrequestid
    ): array {
        if ($paymentrequestid <= 0) {
            throw new \invalid_parameter_exception(
                'Digital payment request ID must be greater than zero.'
            );
        }

        $paymentrequest =
            $this->repository
                ->find_by_id(
                    $paymentrequestid,
                    MUST_EXIST
                );

        $providerstatus =
            $this->providers->check(
                $paymentrequest
            );

        $status =
            $providerstatus->status;

        $reason =
            $providerstatus->reason;

        if (
            $providerstatus->is_paid() &&
            !in_array(
                strtolower(
                    (string)$paymentrequest->status
                ),
                [
                    'paid',
                    'completed',
                ],
                true
            )
        ) {
            $this->complete_payment(
                $paymentrequest
            );
        } else {
            $message =
                $reason !== ''
                    ? '[manual_check] ' .
                        $status .
                        ' - ' .
                        $reason
                    : '';

            $this->repository
                ->record_manual_check(
                    (int)$paymentrequest->id,
                    $message,
                    time()
                );
        }

        return [
            'status' => $status,
            'reason' => $reason,
        ];
    }

    private function complete_payment(
        \stdClass $paymentrequest
    ): void {
        $fresh =
            $this->repository
                ->find_by_id(
                    (int)$paymentrequest->id,
                    IGNORE_MISSING
                );

        if (!$fresh) {
            return;
        }

        if (
            in_array(
                strtolower(
                    (string)$fresh->status
                ),
                [
                    'paid',
                    'completed',
                ],
                true
            )
        ) {
            return;
        }

        if (
            strtolower(
                (string)$fresh->status
            ) !== 'pending'
        ) {
            return;
        }

        $event =
            new InternalEvent(
                'checkout_completed',
                [
                    'payment_request_id' =>
                        (string)$fresh->id,
                    'currency' =>
                        (string)$fresh->currency,
                    'amount_minor' =>
                        (int)$fresh->amount_minor,
                    'meta' => [
                        'payment_context' =>
                            'digital_product',
                        'provider' =>
                            (string)$fresh->payment_provider,
                        'session' =>
                            (string)$fresh->sessionid,
                        'orderId' =>
                            (string)$fresh->sessionid,
                    ],
                ]
            );

        digital_payment_service::
            on_checkout_completed(
                $event
            );
    }

    private function must_mark_failed(
        DigitalPaymentProviderStatus $status
    ): bool {
        if ($status->is_declined()) {
            return true;
        }

        if (
            $status->is_unknown() &&
            stripos(
                $status->reason,
                'No sessionid'
            ) !== false
        ) {
            return true;
        }

        return
            $status->is_pending() &&
            stripos(
                $status->reason,
                'payment_status: unpaid'
            ) !== false;
    }

    private function normalize_options(
        array $options
    ): array {
        $limit =
            (int)(
                $options['limit']
                ?? 5
            );

        $minage =
            (int)(
                $options['minage']
                ?? 300
            );

        $maxage =
            (int)(
                $options['maxage']
                ?? 2 * DAYSECS
            );

        $limit = max(
            1,
            min(
                100,
                $limit
            )
        );

        $minage = max(
            0,
            min(
                7 * DAYSECS,
                $minage
            )
        );

        $maxage = max(
            $minage + 1,
            min(
                90 * DAYSECS,
                $maxage
            )
        );

        return [
            'limit' => $limit,
            'minage' => $minage,
            'maxage' => $maxage,
        ];
    }

    private function safe_exception_message(
        \Throwable $exception
    ): string {
        $message =
            trim(
                $exception->getMessage()
            );

        if ($message === '') {
            return
                get_class($exception);
        }

        $patterns = [
            '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
            '/Basic\s+[A-Za-z0-9+\/=]+/i',
            '/sk_(?:live|test)_[A-Za-z0-9]+/i',
            '/([?&](?:token|password|secret|api_key)=)[^&\s]+/i',
            '/((?:token|password|secret|api_key)\s*[=:]\s*)[^\s,;]+/i',
        ];

        $replacements = [
            'Bearer [redacted]',
            'Basic [redacted]',
            '[redacted Stripe key]',
            '$1[redacted]',
            '$1[redacted]',
        ];

        $message =
            preg_replace(
                $patterns,
                $replacements,
                $message
            ) ?? $message;

        if (
            \core_text::strlen($message) >
            1500
        ) {
            $message =
                \core_text::substr(
                    $message,
                    0,
                    1500
                ) .
                '…';
        }

        return $message;
    }
}