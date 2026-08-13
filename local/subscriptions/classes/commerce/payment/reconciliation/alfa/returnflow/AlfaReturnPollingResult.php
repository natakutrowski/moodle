<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationInspection;

/** One browser-poll outcome for an Alfa payment awaiting provider propagation. */
final class AlfaReturnPollingResult {
    public const COMPLETE = 'complete';
    public const PENDING = 'pending';
    public const UNSAFE = 'unsafe';

    public function __construct(
        public readonly string $status,
        public readonly AlfaPaymentReconciliationInspection $inspection
    ) {
    }
}
