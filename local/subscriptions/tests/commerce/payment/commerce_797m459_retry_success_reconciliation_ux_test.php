<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\payment;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\order\presentation\CommercePostPaymentStateResolver;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentProviderStatus;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationFinalizerInterface;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentStatusProbeInterface;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

/** M4.5.9: a successful retry must never flash a stale local failure. */
final class commerce_797m459_retry_success_reconciliation_ux_test extends advanced_testcase {
    public function test_browser_success_overrides_stale_failed_snapshot_with_pending(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve(
            $this->order(CommercePaymentAttemptStatus::FAILED),
            'success'
        );

        self::assertSame('pending', $state->code);
        self::assertFalse($state->canretry);
        self::assertFalse($state->showaccesses);
    }

    public function test_browser_success_overrides_stale_cancelled_snapshot_with_pending(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve(
            $this->order(CommercePaymentAttemptStatus::CANCELLED),
            'success'
        );

        self::assertSame('pending', $state->code);
        self::assertFalse($state->canretry);
    }

    public function test_real_failure_remains_failed(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve(
            $this->order(CommercePaymentAttemptStatus::FAILED),
            'failure'
        );

        self::assertSame('failed', $state->code);
        self::assertTrue($state->canretry);
    }

    public function test_paid_durable_state_still_beats_stale_browser_failure(): void {
        $state = (new CommercePostPaymentStateResolver())->resolve(
            $this->order(CommercePaymentAttemptStatus::PAID, true),
            'failure'
        );

        self::assertSame('success', $state->code);
        self::assertTrue($state->showaccesses);
    }

    public function test_stripe_paid_provider_can_reconcile_local_failed_attempt(): void {
        $inspection = $this->stripe_inspection(
            CommercePaymentAttemptStatus::FAILED,
            true
        );

        self::assertTrue($inspection->providerpaid);
        self::assertTrue($inspection->reconcilable);
        self::assertFalse($inspection->alreadycomplete);
        self::assertNotContains('campus_payment_terminal_failed', $inspection->blockers);
        self::assertSame([], $inspection->blockers);
    }

    public function test_stripe_unpaid_provider_keeps_local_failed_attempt_unsafe(): void {
        $inspection = $this->stripe_inspection(
            CommercePaymentAttemptStatus::FAILED,
            false
        );

        self::assertFalse($inspection->providerpaid);
        self::assertFalse($inspection->reconcilable);
        self::assertContains('provider_not_paid', $inspection->blockers);
        self::assertContains('campus_payment_terminal_failed', $inspection->blockers);
    }

    public function test_stripe_paid_provider_never_resurrects_refunded_attempt(): void {
        $inspection = $this->stripe_inspection(
            CommercePaymentAttemptStatus::REFUNDED,
            true
        );

        self::assertFalse($inspection->reconcilable);
        self::assertContains('campus_payment_terminal_refunded', $inspection->blockers);
    }

    public function test_order_result_uses_provider_specific_reconciliation_code(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');

        self::assertIsString($source);
        self::assertStringContainsString("? 'stripe_reconciliation'", $source);
        self::assertStringContainsString(": 'alfa_reconciliation'", $source);
    }

    private function stripe_inspection(string $localstatus, bool $providerpaid): object {
        global $DB;
        $this->resetAfterTest(true);

        $reference = 'cmp_' . substr(hash('sha256', uniqid('', true)), 0, 24);
        $uuid = substr(hash('sha256', 'uuid-' . $reference), 0, 32);
        $now = time();

        $purchaseid = (int)$DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object)[
                'purchaseuuid' => $uuid,
                'reference' => $reference,
                'type' => 'course_access',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'm459@example.test',
                'status' => CommercePurchaseStatus::PAYMENT_PENDING,
                'currency' => 'EUR',
                'subtotalminor' => 100,
                'discountminor' => 0,
                'totalminor' => 100,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $paymentid = (int)$DB->insert_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            (object)[
                'purchaseid' => $purchaseid,
                'sequence' => 0,
                'provider' => 'stripe',
                'providerreference' => 'cs_test_m459_' . substr($uuid, 0, 8),
                'providerorderid' => null,
                'status' => $localstatus,
                'currency' => 'EUR',
                'amountminor' => 100,
                'transactionid' => null,
                'legacyrequestid' => null,
                'paidat' => null,
                'metadatajson' => '{}',
                'paymenturl' => null,
                'providerpayload' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $checkoutstatus = $providerpaid ? 'complete' : 'open';
        $paymentstatus = $providerpaid ? 'paid' : 'unpaid';
        $sessionid = 'cs_test_m459_' . substr($uuid, 0, 8);
        $providerevent = new InternalEvent('checkout_completed', [
            'amount_minor' => 100,
            'currency' => 'EUR',
            'meta' => [
                'provider' => 'stripe',
                'session' => $sessionid,
            ],
        ]);
        $providerstatus = new StripePaymentProviderStatus(
            $sessionid,
            'test',
            $checkoutstatus,
            $paymentstatus,
            100,
            'EUR',
            $providerevent
        );

        $probe = new class($providerstatus) implements StripePaymentStatusProbeInterface {
            public function __construct(private readonly StripePaymentProviderStatus $status) {
            }

            public function probe(string $sessionid): StripePaymentProviderStatus {
                return $this->status;
            }
        };

        $finalizer = new class implements StripePaymentReconciliationFinalizerInterface {
            public function finalize(InternalEvent $event): void {
            }
        };

        $service = new StripePaymentReconciliationService(
            $DB,
            new CommercePaymentRepository($DB),
            $probe,
            $finalizer
        );

        return $service->inspect_payment($paymentid);
    }

    private function order(string $paymentstatus, bool $available = false): CommerceOrderPresentation {
        $access = new class($available) {
            public function __construct(public bool $available) {
            }
        };
        $item = new class($access) {
            public array $accesses;
            public function __construct(object $access) {
                $this->accesses = [$access];
            }
        };

        return new CommerceOrderPresentation(
            1,
            'uuid',
            'cmp_m459',
            'course',
            2,
            'm459@example.test',
            'EUR',
            100,
            'active',
            $paymentstatus,
            'pending',
            'stripe',
            time(),
            time(),
            [$item],
            [],
            []
        );
    }
}
