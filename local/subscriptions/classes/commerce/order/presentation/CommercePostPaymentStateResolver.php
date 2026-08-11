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

        if ($browserresult === 'cancel' || in_array($paymentstatus, self::CANCELLED, true)) {
            return new CommercePostPaymentState('cancelled', 'warning', true, false);
        }
        if ($browserresult === 'failure' || in_array($paymentstatus, self::FAILED, true)) {
            return new CommercePostPaymentState('failed', 'danger', true, false);
        }
        if ($order->is_paid()) {
            if ($order->has_available_accesses()) {
                return new CommercePostPaymentState('success', 'success', false, true);
            }
            return new CommercePostPaymentState('processing', 'info', false, false);
        }
        if (in_array($paymentstatus, self::PENDING, true) || $browserresult === 'success') {
            return new CommercePostPaymentState('pending', 'info', false, false);
        }
        return new CommercePostPaymentState('unknown', 'warning', true, false);
    }
}
