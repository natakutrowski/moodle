<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;

/** Human-facing labels and badges for unified Commerce purchases. */
final class CommercePurchasePresentation {
    public static function commercial_status_label(string $status): string {
        return get_string('commerce_purchase_commercial_status_' . self::safe_key($status), 'local_subscriptions');
    }

    public static function commercial_status_badge(string $status): string {
        $class = match ($status) {
            CommerceCommercialStatus::FULFILLED => 'success',
            CommerceCommercialStatus::PAID, CommerceCommercialStatus::TO_FULFILL => 'primary',
            CommerceCommercialStatus::PARTIALLY_FULFILLED => 'warning',
            CommerceCommercialStatus::PAYMENT_FAILED, CommerceCommercialStatus::CANCELLED => 'danger',
            CommerceCommercialStatus::REFUNDED, CommerceCommercialStatus::REPLACED => 'secondary',
            default => 'light text-dark',
        };
        return html_writer::span(s(self::commercial_status_label($status)), 'badge bg-' . $class);
    }

    public static function technical_status_label(string $family, string $status): string {
        $key = 'commerce_purchase_' . self::safe_key($family) . '_status_' . self::safe_key($status);
        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : ucfirst(str_replace('_', ' ', $status));
    }

    public static function technical_status_badge(string $family, string $status): string {
        $normalised = strtolower(trim($status));
        $class = match ($normalised) {
            'paid', 'succeeded', 'success', 'completed', 'fulfilled', 'active' => 'success',
            'failed', 'error', 'cancelled', 'canceled' => 'danger',
            'refunded', 'replaced' => 'secondary',
            'pending', 'created', 'processing', 'queued', 'none' => 'light text-dark',
            default => 'info',
        };
        return html_writer::span(s(self::technical_status_label($family, $status)), 'badge bg-' . $class);
    }


    /**
     * Builds the four labelled business dimensions shown on a purchase record.
     *
     * @return array<int,array{label:string,value:string}>
     */
    public static function status_dimensions(
        int $totalminor,
        string $commercialstatus,
        string $paymentstatus,
        string $fulfillmentstatus
    ): array {
        $paymentvalue = $totalminor === 0
            ? get_string('commerce_purchase_payment_not_required', 'local_subscriptions')
            : self::technical_status_label('payment', $paymentstatus);

        $deliverystatus = $fulfillmentstatus === 'none' ? 'not_started' : $fulfillmentstatus;
        $accessstatus = match (strtolower(trim($fulfillmentstatus))) {
            'completed', 'fulfilled', 'active', 'succeeded', 'success' => 'active',
            'failed', 'error', 'cancelled', 'canceled' => 'blocked',
            default => 'pending',
        };

        $ordervalue = $totalminor === 0 && $commercialstatus === CommerceCommercialStatus::PAID
            ? get_string('commerce_purchase_order_status_completed', 'local_subscriptions')
            : self::commercial_status_label($commercialstatus);

        return [
            [
                'label' => get_string('commerce_purchase_dimension_payment', 'local_subscriptions'),
                'value' => s($paymentvalue),
            ],
            [
                'label' => get_string('commerce_purchase_dimension_order', 'local_subscriptions'),
                'value' => s($ordervalue),
            ],
            [
                'label' => get_string('commerce_purchase_dimension_delivery', 'local_subscriptions'),
                'value' => s(self::technical_status_label('fulfillment', $deliverystatus)),
            ],
            [
                'label' => get_string('commerce_purchase_dimension_access', 'local_subscriptions'),
                'value' => s(get_string('commerce_purchase_access_status_' . $accessstatus, 'local_subscriptions')),
            ],
        ];
    }

    public static function fulfillment_label(string $key): string {
        $normalised = self::safe_key($key);
        $stringkey = 'commerce_purchase_fulfillment_type_' . $normalised;

        return get_string_manager()->string_exists($stringkey, 'local_subscriptions')
            ? get_string($stringkey, 'local_subscriptions')
            : ucfirst(str_replace('_', ' ', $normalised));
    }

    public static function type_label(string $type): string {
        $key = 'commerce_purchase_type_' . self::safe_key($type);
        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : ucfirst(str_replace('_', ' ', $type));
    }

    public static function type_badge(string $type): string {
        $normalised = self::safe_key($type);
        $class = match ($normalised) {
            'subscription' => 'primary',
            'digital' => 'info text-dark',
            'bundle' => 'warning text-dark',
            default => 'secondary',
        };

        return html_writer::span(
            s(self::type_label($normalised)),
            'badge bg-' . $class . ' commerce-purchase-type-badge'
        );
    }

    public static function money(int $minor, string $currency): string {
        return format_float($minor / 100, 2) . ' ' . strtoupper($currency);
    }

    private static function safe_key(string $value): string {
        $value = preg_replace('/[^a-z0-9_]+/', '_', strtolower(trim($value))) ?? '';
        return trim($value, '_') ?: 'unknown';
    }
}
