<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\Provider;
use moodle_database;
final class StripePaymentReconciliationService implements StripePaymentReconciliationEngineInterface {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePaymentRepository $payments,
        private readonly StripePaymentStatusProbeInterface $probe,
        private readonly StripePaymentReconciliationFinalizerInterface $finalizer
    ) {}
    public static function create(moodle_database $database): self {
        return new self($database,new CommercePaymentRepository($database),new StripeCheckoutSessionStatusProbe(),new EventRouterStripePaymentReconciliationFinalizer());
    }
    public function inspect_payment(int $paymentid): StripePaymentReconciliationInspection {
        $attempt=$this->payments->find($paymentid);
        if ($attempt===null) throw new \moodle_exception('commerce_stripe_reconciliation_payment_not_found','local_subscriptions');
        return $this->inspect_attempt($attempt);
    }
    public function reconcile_payment(int $paymentid): StripePaymentReconciliationInspection {
        $i=$this->inspect_payment($paymentid);
        if ($i->alreadycomplete) return $i;
        if (!$i->reconcilable) throw new \moodle_exception('commerce_stripe_reconciliation_not_safe','local_subscriptions','',implode(', ',$i->blockers));
        $e=$i->provider->event;
        $e->meta['provider']=Provider::STRIPE;
        $e->meta['commerce_payment_id']=(string)$i->paymentid;
        $e->meta['commerce_purchase_uuid']=$i->purchaseuuid;
        $e->meta['commerce_reference']=$i->purchasereference;
        $e->meta['reconciliation_source']='stripe_payment_reconciliation';
        $e->meta['reconciliation_checked_at']=time();
        $tx=$this->database->start_delegated_transaction();
        $this->finalizer->finalize($e);
        $tx->allow_commit();
        $after=$this->payments->find($paymentid);
        if ($after===null) throw new \RuntimeException('Stripe payment disappeared after reconciliation.');
        return $this->inspect_attempt($after,$i->provider);
    }
    private function inspect_attempt(CommercePaymentAttempt $a, ?StripePaymentProviderStatus $known=null): StripePaymentReconciliationInspection {
        if ($a->get_provider()!==Provider::STRIPE) throw new \moodle_exception('commerce_stripe_reconciliation_wrong_provider','local_subscriptions');
        $paymentid=(int)$a->get_id();
        $purchase=$this->database->get_record(CommercePersistenceSchema::TABLE_PURCHASE,['purchaseuuid'=>$a->get_purchase_uuid()],'*',MUST_EXIST);
        $sid=trim((string)($a->get_provider_order_id() ?? $a->get_provider_reference() ?? ''));
        if ($sid==='') throw new \moodle_exception('commerce_stripe_reconciliation_missing_session','local_subscriptions');
        $p=$known ?? $this->probe->probe($sid);
        $amount=$p->amountminor!==null && $p->amountminor===$a->get_amount_minor() && $p->amountminor===(int)$purchase->totalminor;
        $currency=$p->currency!==null && $p->currency===$a->get_currency() && $p->currency===strtoupper((string)$purchase->currency);
        $paid=$p->is_paid();
        $complete=in_array($a->get_status(),[CommercePaymentAttemptStatus::PAID,CommercePaymentAttemptStatus::COMPLETED],true)
            && (string)$purchase->status===CommercePurchaseStatus::FULFILLED;
        $blockers=[];
        if (!$paid) $blockers[]='provider_not_paid';
        if (!$amount) $blockers[]='amount_mismatch';
        if (!$currency) $blockers[]='currency_mismatch';
        if ($paid && $p->event->type!=='checkout_completed') $blockers[]='provider_event_not_completed';
        if (in_array($a->get_status(),[CommercePaymentAttemptStatus::REFUNDED,CommercePaymentAttemptStatus::CANCELLED,CommercePaymentAttemptStatus::FAILED,CommercePaymentAttemptStatus::ERROR],true)) {
            $blockers[]='campus_payment_terminal_'.$a->get_status();
        }
        return new StripePaymentReconciliationInspection(
            $paymentid,(int)$purchase->id,(string)$purchase->reference,(string)$purchase->purchaseuuid,
            $a->get_status(),(string)$purchase->status,$a->get_amount_minor(),$a->get_currency(),$sid,$p,
            $amount,$currency,$paid,!$complete && $blockers===[],$complete,$blockers
        );
    }
}
