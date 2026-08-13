<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\EventRouter;

/** Reuses the exact provider event pipeline used by normal payment returns/webhooks. */
final class EventRouterAlfaPaymentReconciliationFinalizer implements AlfaPaymentReconciliationFinalizerInterface {
    public function finalize(InternalEvent $event): void {
        EventRouter::handle($event);
    }
}
