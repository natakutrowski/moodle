<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Converts technical Commerce states into stable customer-facing labels. */
final class CommerceCustomerStatusResolver {
    public function resolve_order(string $status): array {
        return $this->resolve($status, 'order');
    }

    public function resolve_payment(string $status): array {
        return $this->resolve($status, 'payment');
    }

    public function resolve_access(string $status): ?array {
        $normalised = $this->normalise($status);
        if ($normalised === '' || $normalised === 'none') {
            return null;
        }
        return $this->resolve($normalised, 'access');
    }

    public function resolve_timeline(string $status): array {
        return $this->resolve($status, 'timeline');
    }

    private function resolve(string $status, string $scope): array {
        $status = $this->normalise($status);
        $success = ['paid', 'captured', 'completed', 'succeeded', 'success', 'fulfilled', 'delivered', 'active'];
        $warning = ['pending', 'processing', 'planned', 'created', 'initiated', 'none', ''];
        $danger = ['failed', 'error', 'cancelled', 'canceled', 'expired', 'refunded'];

        $class = in_array($status, $success, true) ? 'success'
            : (in_array($status, $danger, true) ? 'danger' : 'warning');

        $key = match ($scope) {
            'payment' => match (true) {
                in_array($status, $success, true) => 'commerce_i410_payment_received',
                in_array($status, ['failed', 'error'], true) => 'commerce_i410_payment_failed',
                in_array($status, ['cancelled', 'canceled'], true) => 'commerce_i410_payment_cancelled',
                default => 'commerce_i410_payment_pending',
            },
            'access' => match (true) {
                in_array($status, $success, true) => 'commerce_i410_access_available',
                in_array($status, ['failed', 'error'], true) => 'commerce_i410_access_failed',
                default => 'commerce_i410_access_preparing',
            },
            'timeline' => match (true) {
                in_array($status, $success, true) => 'commerce_i410_step_completed',
                in_array($status, ['failed', 'error', 'cancelled', 'canceled'], true) => 'commerce_i410_step_failed',
                default => 'commerce_i410_step_pending',
            },
            default => match (true) {
                in_array($status, $success, true) => 'commerce_i410_order_confirmed',
                in_array($status, ['cancelled', 'canceled'], true) => 'commerce_i410_order_cancelled',
                in_array($status, ['failed', 'error'], true) => 'commerce_i410_order_failed',
                default => 'commerce_i410_order_processing',
            },
        };

        return ['label' => get_string($key, 'local_subscriptions'), 'class' => $class];
    }

    private function normalise(string $status): string {
        return strtolower(trim($status));
    }
}
