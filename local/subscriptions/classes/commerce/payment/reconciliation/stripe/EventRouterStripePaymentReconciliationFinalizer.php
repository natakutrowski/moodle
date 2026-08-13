<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\EventRouter;
final class EventRouterStripePaymentReconciliationFinalizer implements StripePaymentReconciliationFinalizerInterface {
    public function finalize(InternalEvent $event): void { EventRouter::handle($event); }
}
