<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\recovery;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivator;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePaidPurchaseCompleter;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyRepository;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyService;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

/** Diagnoses and safely repairs interrupted Native Commerce checkouts. */
final class CommerceCheckoutRecoveryService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePaymentRepository $payments,
        private readonly CommerceIdempotencyService $idempotency
    ) {
    }

    public static function create(\moodle_database $db): self {
        return new self(
            $db,
            new CommercePaymentRepository($db),
            new CommerceIdempotencyService(new CommerceIdempotencyRepository())
        );
    }

    public function diagnose(string $identifier, string $kind = 'reference'): CommerceRecoveryDiagnostic {
        $purchase = $this->resolve_purchase(trim($identifier), $kind);
        if ($purchase === null) {
            return new CommerceRecoveryDiagnostic(null, [], [], null, ['purchase_not_found'], []);
        }

        $paymentrows = array_values($this->db->get_records(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => (int)$purchase->id],
            'sequence DESC, id DESC'
        ));
        $fulfillmentrows = array_values($this->db->get_records(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            ['purchaseid' => (int)$purchase->id],
            'sequence ASC, id ASC'
        ));

        $paidpayment = null;
        foreach ($paymentrows as $paymentrow) {
            if (in_array((string)$paymentrow->status, [
                CommercePaymentAttemptStatus::PAID,
                CommercePaymentAttemptStatus::COMPLETED,
            ], true)) {
                $paidpayment = $paymentrow;
                break;
            }
        }

        $guest = (new CommerceGuestCheckoutSessionRepository($this->db))
            ->find_by_purchase_reference((string)$purchase->reference);
        $guestarray = $guest === null ? null : [
            'reference' => $guest->get_reference(),
            'status' => $guest->get_status(),
            'userid' => $guest->get_user_id(),
            'email' => $guest->get_email(),
        ];

        $issues = [];
        $actions = [];
        if ($paymentrows === []) {
            $issues[] = 'payment_missing';
        } elseif ($paidpayment === null) {
            $issues[] = 'payment_not_paid';
        }

        $fulfilled = (string)$purchase->status === CommercePurchaseStatus::FULFILLED;
        if ($paidpayment !== null && !$fulfilled) {
            $issues[] = 'paid_purchase_not_fulfilled';
            $actions[] = 'complete_fulfillment';
        }

        if ($fulfilled && $fulfillmentrows === []) {
            $issues[] = 'fulfilled_purchase_without_fulfillment_records';
        }

        if ($guest !== null && $paidpayment !== null && $guest->get_status() !== 'active') {
            $issues[] = 'paid_guest_account_not_active';
            $actions[] = 'activate_guest_account';
        }

        return new CommerceRecoveryDiagnostic(
            $this->purchase_to_array($purchase),
            array_map([$this, 'payment_to_array'], $paymentrows),
            array_map([$this, 'fulfillment_to_array'], $fulfillmentrows),
            $guestarray,
            array_values(array_unique($issues)),
            array_values(array_unique($actions))
        );
    }

    public function execute(string $identifier, string $kind = 'reference'): CommerceRecoveryExecutionResult {
        $before = $this->diagnose($identifier, $kind);
        if (!$before->is_found()) {
            throw new \RuntimeException('The Commerce checkout recovery target was not found.');
        }

        $purchase = $before->get_purchase();
        $key = 'recover:' . $purchase['purchaseuuid'];
        $execution = $this->idempotency->execute(
            'checkout_recovery',
            $key,
            ['purchaseuuid' => $purchase['purchaseuuid']],
            function () use ($before, $purchase): array {
                $executed = [];
                $payment = $this->latest_paid_payment($before->get_payments());

                if (in_array('complete_fulfillment', $before->get_actions(), true)) {
                    if ($payment === null) {
                        throw new \RuntimeException('A paid Commerce payment is required for fulfillment recovery.');
                    }
                    $event = new InternalEvent('checkout_completed', [
                        'currency' => $payment['currency'],
                        'amount_minor' => $payment['amountminor'],
                        'meta' => [
                            'commerce_payment_id' => $payment['id'],
                            'commerce_purchase_uuid' => $purchase['purchaseuuid'],
                            'commerce_reference' => $purchase['reference'],
                            'provider' => $payment['provider'],
                        ],
                    ]);
                    (new CommerceNativePaidPurchaseCompleter($this->db, $this->payments))->complete($event);
                    $executed[] = 'complete_fulfillment';
                }

                if (in_array('activate_guest_account', $before->get_actions(), true)) {
                    (new CommerceGuestAccountActivator(
                        $this->db,
                        new CommerceGuestCheckoutSessionRepository($this->db)
                    ))->activate_for_purchase($purchase['reference']);
                    $executed[] = 'activate_guest_account';
                }

                return ['executed_actions' => $executed];
            }
        );

        return new CommerceRecoveryExecutionResult(
            $before,
            $this->diagnose($purchase['reference'], 'reference'),
            $execution['result']['executed_actions'] ?? [],
            (bool)$execution['replayed']
        );
    }

    private function resolve_purchase(string $identifier, string $kind): ?\stdClass {
        if ($identifier === '') {
            return null;
        }
        if ($kind === 'payment') {
            $payment = $this->db->get_record(CommercePersistenceSchema::TABLE_PAYMENT, ['id' => (int)$identifier], '*', IGNORE_MISSING);
            if ($payment === false) {
                return null;
            }
            $purchase = $this->db->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['id' => (int)$payment->purchaseid],
                '*',
                IGNORE_MISSING
            );
            return $purchase === false ? null : $purchase;
        }
        if ($kind === 'session') {
            $session = (new CommerceGuestCheckoutSessionRepository($this->db))->find_by_token($identifier);
            if ($session === null || $session->get_purchase_reference() === null) {
                return null;
            }
            $identifier = $session->get_purchase_reference();
            $kind = 'reference';
        }
        $field = $kind === 'purchase' ? 'purchaseuuid' : 'reference';
        $record = $this->db->get_record(CommercePersistenceSchema::TABLE_PURCHASE, [$field => $identifier], '*', IGNORE_MISSING);
        return $record === false ? null : $record;
    }

    private function latest_paid_payment(array $payments): ?array {
        foreach ($payments as $payment) {
            if (in_array($payment['status'], ['paid', 'completed'], true)) {
                return $payment;
            }
        }
        return null;
    }

    private function purchase_to_array(\stdClass $row): array {
        return ['id' => (int)$row->id, 'purchaseuuid' => (string)$row->purchaseuuid, 'reference' => (string)$row->reference,
            'userid' => empty($row->userid) ? null : (int)$row->userid, 'customeremail' => (string)$row->customeremail,
            'status' => (string)$row->status, 'currency' => (string)$row->currency, 'totalminor' => (int)$row->totalminor];
    }
    private function payment_to_array(\stdClass $row): array {
        return ['id' => (int)$row->id, 'sequence' => (int)$row->sequence, 'provider' => (string)$row->provider,
            'status' => (string)$row->status, 'currency' => (string)$row->currency, 'amountminor' => (int)$row->amountminor,
            'providerreference' => $row->providerreference === null ? null : (string)$row->providerreference,
            'transactionid' => $row->transactionid === null ? null : (string)$row->transactionid];
    }
    private function fulfillment_to_array(\stdClass $row): array {
        return ['id' => (int)$row->id, 'sequence' => (int)$row->sequence, 'reference' => (string)$row->reference,
            'fulfillmentkey' => (string)$row->fulfillmentkey, 'status' => (string)$row->status,
            'idempotencykey' => (string)$row->idempotencykey];
    }
}
