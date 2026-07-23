<?php

namespace local_subscriptions\commerce\postpayment;

defined('MOODLE_INTERNAL') || die();

/**
 * Structured, secret-free logger for the post-payment migration.
 */
final class CommercePostPaymentLogger {

    public function log(string $stage, string $result, array $context = []): void {
        $safe = [
            'stage' => $stage,
            'result' => $result,
            'provider' => $context['provider'] ?? null,
            'event_type' => $context['event_type'] ?? null,
            'payment_request_id' => $context['payment_request_id'] ?? null,
            'currency' => $context['currency'] ?? null,
            'legacy_status' => $context['legacy_status'] ?? null,
            'commerce_status' => $context['commerce_status'] ?? null,
            'correlation_id' => $context['correlation_id'] ?? null,
            'issues' => $context['issues'] ?? [],
        ];

        error_log(
            '[Commerce post-payment] ' . json_encode(
                array_filter($safe, static fn(mixed $value): bool => $value !== null),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
