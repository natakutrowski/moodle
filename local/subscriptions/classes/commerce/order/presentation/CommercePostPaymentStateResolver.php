<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Resolves browser-return and durable Native states into one customer-facing state. */
final class CommercePostPaymentStateResolver {
    private const FAILED = ['failed', 'declined', 'error', 'expired'];
    private const CANCELLED = ['cancelled', 'canceled'];
    private const PENDING = ['created', 'pending', 'redirected', 'processing', 'initiated', 'unknown'];

    public function resolve(CommerceOrderPresentation $order, string $browserresult = ''): CommercePostPaymentState {
        $browserresult = strtolower(trim($browserresult));
        $paymentstatus = strtolower(trim($order->paymentstatus));

        // The durable payment state is authoritative. A provider/browser return can
        // legitimately be stale after a retry succeeds on the same purchase, so a
        // historical ?result=failure/cancel must never downgrade an already paid order.
        if ($order->is_paid()) {
            if ($order->has_available_accesses()) {
                return new CommercePostPaymentState('success', 'success', false, true);
            }
            return new CommercePostPaymentState('processing', 'info', false, false);
        }
        // A provider/browser success return is stronger than a stale local
        // FAILED/CANCELLED snapshot from an earlier attempt. Do not flash a
        // false failure while the provider reconciliation/webhook catches up.
        // This state never exposes access: it only enables the confirmation
        // polling surface until durable PAID/COMPLETED is observed.
        if ($browserresult === 'success') {
            return new CommercePostPaymentState('pending', 'info', false, false);
        }
        if ($browserresult === 'cancel') {
            return new CommercePostPaymentState('cancelled', 'warning', true, false);
        }
        if ($browserresult === 'failure') {
            return new CommercePostPaymentState('failed', 'danger', true, false);
        }
        if (in_array($paymentstatus, self::CANCELLED, true)) {
            return new CommercePostPaymentState('cancelled', 'warning', true, false);
        }
        if (in_array($paymentstatus, self::FAILED, true)) {
            return new CommercePostPaymentState('failed', 'danger', true, false);
        }
        if (in_array($paymentstatus, self::PENDING, true)) {
            return new CommercePostPaymentState('pending', 'info', false, false);
        }
        return new CommercePostPaymentState('unknown', 'warning', true, false);
    }
}
