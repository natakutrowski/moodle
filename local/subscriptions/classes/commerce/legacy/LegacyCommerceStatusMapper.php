<?php

namespace local_subscriptions\commerce\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePayment;

/**
 * Normalises historical statuses without modifying stored data.
 */
final class LegacyCommerceStatusMapper {

    public static function payment_status(?string $status): string {
        $status = strtolower(trim((string)$status));

        return match ($status) {
            'paid' => CommercePayment::STATUS_PAID,
            'completed' => CommercePayment::STATUS_COMPLETED,
            'pending',
            'queued' => CommercePayment::STATUS_PENDING,
            'failed',
            'declined' => CommercePayment::STATUS_FAILED,
            'cancelled',
            'canceled' => CommercePayment::STATUS_CANCELLED,
            'error' => CommercePayment::STATUS_ERROR,
            default => CommercePayment::STATUS_UNKNOWN,
        };
    }

    public static function purchase_status(?string $status): string {
        $status = strtolower(trim((string)$status));

        if ($status === '') {
            return 'unknown';
        }

        return $status;
    }
}