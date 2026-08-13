<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\dto\InternalEvent;

/** Runs the normal Commerce post-payment pipeline for a verified provider event. */
interface AlfaPaymentReconciliationFinalizerInterface {
    public function finalize(InternalEvent $event): void;
}
