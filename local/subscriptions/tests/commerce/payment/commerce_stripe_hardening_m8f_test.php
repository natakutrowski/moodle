<?php
declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentProviderStatus;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationEngineInterface;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationInspection;
use local_subscriptions\commerce\payment\reconciliation\stripe\returnflow\StripeReturnPollingResult;
use local_subscriptions\commerce\payment\reconciliation\stripe\returnflow\StripeReturnPollingService;
use local_subscriptions\payment\dto\InternalEvent;
final class commerce_stripe_hardening_m8f_test extends advanced_testcase {
    public function test_paid_checkout_is_reconciled_by_return_polling(): void {
        $before=$this->inspection(true,false,[]); $after=$this->inspection(false,true,[]);
        $engine=new m8f_engine($before,$after);
        $result=(new StripeReturnPollingService($engine))->check(99);
        self::assertSame(StripeReturnPollingResult::COMPLETE,$result->status);
        self::assertSame(1,$engine->reconcilecalls);
    }
    public function test_unpaid_checkout_remains_pending(): void {
        $pending=$this->inspection(false,false,['provider_not_paid']); $engine=new m8f_engine($pending,$pending);
        $result=(new StripeReturnPollingService($engine))->check(99);
        self::assertSame(StripeReturnPollingResult::PENDING,$result->status); self::assertSame(0,$engine->reconcilecalls);
    }
    public function test_safety_mismatch_is_unsafe(): void {
        $b=$this->inspection(false,false,['amount_mismatch']); $engine=new m8f_engine($b,$b);
        self::assertSame(StripeReturnPollingResult::UNSAFE,(new StripeReturnPollingService($engine))->check(99)->status);
    }
    public function test_webhook_is_hardened(): void {
        global $CFG; $s=file_get_contents($CFG->dirroot.'/local/subscriptions/webhook/stripe.php');
        self::assertStringContainsString('StripeWebhookVerifier',$s);
        self::assertStringContainsString('invalid_signature_or_payload',$s);
        self::assertStringContainsString("event->type!=='payment_pending'",$s);
    }
    public function test_gateway_requires_paid_status_and_async_events(): void {
        global $CFG; $s=file_get_contents($CFG->dirroot.'/local/subscriptions/classes/payment/stripe/StripeGateway.php');
        self::assertStringContainsString('checkout.session.async_payment_succeeded',$s);
        self::assertStringContainsString('checkout.session.async_payment_failed',$s);
        self::assertStringContainsString("\$paymentstatus !== 'paid'",$s);
    }
    public function test_return_splash_supports_stripe(): void {
        global $CFG; $r=file_get_contents($CFG->dirroot.'/local/subscriptions/payment/return.php');
        $o=file_get_contents($CFG->dirroot.'/local/subscriptions/order_result.php');
        self::assertStringContainsString('StripePaymentReconciliationService',$r);
        self::assertStringContainsString('stripe_return_poll.php',$o);
        self::assertStringContainsString("['alfa', 'stripe']",$o);
    }
    public function test_task_wiring_is_autoloadable(): void {
        self::assertTrue(class_exists(\local_subscriptions\task\reconcile_stripe_payments_task::class));
        self::assertTrue(class_exists(\local_subscriptions\commerce\task\job\StripePaymentReconciliationJob::class));
    }
    private function inspection(bool $rec,bool $complete,array $blockers): StripePaymentReconciliationInspection {
        $paid=$rec||$complete;
        $p=new StripePaymentProviderStatus('cs_test_m8f','test',$paid?'complete':'open',$paid?'paid':'unpaid',3900,'EUR',
            new InternalEvent('checkout_completed',['amount_minor'=>3900,'currency'=>'EUR','meta'=>['provider'=>'stripe','session'=>'cs_test_m8f']]));
        return new StripePaymentReconciliationInspection(99,8,'cmp_m8f','aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',$complete?'paid':'redirected',$complete?'fulfilled':'payment_pending',3900,'EUR','cs_test_m8f',$p,true,true,$paid,$rec,$complete,$blockers);
    }
}
final class m8f_engine implements StripePaymentReconciliationEngineInterface {
    public int $reconcilecalls=0;
    public function __construct(private readonly StripePaymentReconciliationInspection $before,private readonly StripePaymentReconciliationInspection $after){}
    public function inspect_payment(int $paymentid): StripePaymentReconciliationInspection{return $this->before;}
    public function reconcile_payment(int $paymentid): StripePaymentReconciliationInspection{$this->reconcilecalls++;return $this->after;}
}
