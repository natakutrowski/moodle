<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe\returnflow;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationInspection;
final class StripeReturnPollingResult {
    public const COMPLETE='complete'; public const PENDING='pending'; public const UNSAFE='unsafe';
    public function __construct(public readonly string $status,public readonly StripePaymentReconciliationInspection $inspection) {}
}
