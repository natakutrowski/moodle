<?php

namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\dualwrite\CommerceDualWriteBridge;
use local_subscriptions\commerce\postpayment\SubscriptionPostPaymentProcessor;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\PaidPaymentRequestRepairRepository;
use local_subscriptions\commerce\task\support\TaskLock;
use local_subscriptions\domain\PaymentService;
use local_subscriptions\payment\dto\InternalEvent;

final class PaidPaymentRequestRepairJob implements CommerceTaskJob {

    public function __construct(
        private readonly ?PaidPaymentRequestRepairRepository $repository = null,
        private readonly ?SubscriptionPostPaymentProcessor $processor = null,
        private readonly int $limit = 100,
    ) {
    }

    public function run(): TaskExecutionResult {
        $result = new TaskExecutionResult('paid_payment_request_repair');
        $lock = TaskLock::acquire('payment_request.repair');

        if (!$lock) {
            $result->mark_locked();
            return $result->finish();
        }

        try {
            $repository = $this->repository ?? new PaidPaymentRequestRepairRepository();
            $processor = $this->processor ?? new SubscriptionPostPaymentProcessor();

            foreach ($repository->find_candidates(time() - 60, $this->limit) as $paymentrequest) {
                $result->increment('scanned');

                $quarantinereason = $this->quarantine_reason($paymentrequest);
                if ($quarantinereason !== null) {
                    $repository->quarantine((int) $paymentrequest->id, $quarantinereason);
                    $result->increment('quarantined');
                    continue;
                }

                try {
                    $event = $this->create_event($paymentrequest);
                    $processing = $processor->process($event);

                    if ($processing->requires_legacy()) {
                        PaymentService::on_checkout_completed($event);
                    }

                    $freshrequest = $repository->find((int) $paymentrequest->id);
                    if (!$freshrequest || empty($freshrequest->subscriptionid)) {
                        throw new \RuntimeException(
                            'Commerce-controlled subscription fulfillment did not create or link a subscription.',
                        );
                    }

                    CommerceDualWriteBridge::subscription(
                        (int) $freshrequest->subscriptionid,
                        'repair_paid_payment_request',
                    );

                    $result->increment('repaired');
                } catch (\Throwable $exception) {
                    $result->add_error((int) $paymentrequest->id, $exception);
                }
            }

            return $result->finish();
        } finally {
            $lock->release();
        }
    }

    private function quarantine_reason(\stdClass $paymentrequest): ?string {
        if (empty($paymentrequest->repair_plan_exists)) {
            return 'missing subscription plan';
        }

        if (empty($paymentrequest->repair_plan_active)) {
            return 'inactive subscription plan';
        }

        $requestemail = trim((string) ($paymentrequest->email ?? ''));
        $userid = (int) ($paymentrequest->userid ?? 0);
        $linkeduserexists = !empty($paymentrequest->repair_user_exists);
        $linkeduserdeleted = !empty($paymentrequest->repair_user_deleted);
        $linkeduseremail = trim((string) ($paymentrequest->repair_user_email ?? ''));

        if ($requestemail !== '') {
            return null;
        }

        if ($userid <= 0) {
            return 'missing customer identity: userid and email are empty';
        }

        if (!$linkeduserexists) {
            return 'linked Moodle user does not exist';
        }

        if ($linkeduserdeleted) {
            return 'linked Moodle user is deleted';
        }

        if ($linkeduseremail === '') {
            return 'linked Moodle user has no email';
        }

        return null;
    }

    private function create_event(\stdClass $paymentrequest): InternalEvent {
        $provider = strtolower((string) ($paymentrequest->payment_provider ?? ''));
        $session = (string) ($paymentrequest->sessionid ?? $paymentrequest->transactionid ?? '');
        $amountminor = property_exists($paymentrequest, 'amount_minor')
            ? (int) $paymentrequest->amount_minor
            : (int) round(((float) ($paymentrequest->price ?? 0)) * 100);

        return new InternalEvent(
            'checkout_completed',
            [
                'payment_request_id' => (string) $paymentrequest->id,
                'currency' => strtoupper((string) ($paymentrequest->currency ?? '')),
                'amount_minor' => $amountminor,
                'meta' => [
                    'payment_context' => 'subscription',
                    'provider' => $provider,
                    'session' => $session,
                    'transactionid' => (string) ($paymentrequest->transactionid ?? ''),
                    'repair' => true,
                ],
            ],
        );
    }
}
