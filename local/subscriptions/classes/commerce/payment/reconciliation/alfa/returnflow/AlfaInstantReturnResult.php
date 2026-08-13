<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationInspection;

/** Immutable outcome of the bounded Alfa browser-return reconciliation. */
final class AlfaInstantReturnResult {
    public const COMPLETE = 'complete';
    public const PENDING = 'pending';
    public const UNSAFE = 'unsafe';

    public function __construct(
        public readonly string $status,
        public readonly AlfaPaymentReconciliationInspection $inspection,
        public readonly int $attempts
    ) {
    }

    public function is_complete(): bool {
        return $this->status === self::COMPLETE;
    }

    public function is_pending(): bool {
        return $this->status === self::PENDING;
    }

    public function is_unsafe(): bool {
        return $this->status === self::UNSAFE;
    }
}
