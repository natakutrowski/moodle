<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\dto\InternalEvent;
interface StripePaymentReconciliationFinalizerInterface {
    public function finalize(InternalEvent $event): void;
}
