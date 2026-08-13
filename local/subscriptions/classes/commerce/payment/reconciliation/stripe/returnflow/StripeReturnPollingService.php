<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe\returnflow;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationEngineInterface;
final class StripeReturnPollingService {
    public function __construct(private readonly StripePaymentReconciliationEngineInterface $engine) {}
    public function check(int $paymentid): StripeReturnPollingResult {
        $i=$this->engine->inspect_payment($paymentid);
        if($i->alreadycomplete) return new StripeReturnPollingResult(StripeReturnPollingResult::COMPLETE,$i);
        if($i->reconcilable){
            $a=$this->engine->reconcile_payment($paymentid);
            return new StripeReturnPollingResult($a->alreadycomplete?StripeReturnPollingResult::COMPLETE:StripeReturnPollingResult::PENDING,$a);
        }
        if($i->blockers===['provider_not_paid']) return new StripeReturnPollingResult(StripeReturnPollingResult::PENDING,$i);
        return new StripeReturnPollingResult(StripeReturnPollingResult::UNSAFE,$i);
    }
}
