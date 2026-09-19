<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

defined('MOODLE_INTERNAL') || die();

/** Human-readable labels for stable provider/refund reason keys. */
final class CommerceRefundReasonPresenter {
    public function label(?string $reason): string {
        $reason = trim((string)$reason);
        if ($reason === '') {
            return '';
        }

        $stringkey = match (strtolower($reason)) {
            'requested_by_customer' => 'commerce_refund_reason_requested_by_customer',
            'duplicate' => 'commerce_refund_reason_duplicate',
            'fraudulent' => 'commerce_refund_reason_fraudulent',
            default => null,
        };

        return $stringkey === null
            ? $reason
            : get_string($stringkey, 'local_subscriptions');
    }
}
