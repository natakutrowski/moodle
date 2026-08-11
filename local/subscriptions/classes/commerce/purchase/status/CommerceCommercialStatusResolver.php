<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\status;

defined('MOODLE_INTERNAL') || die();

/** Resolves one deterministic CRM status from purchase, payment and fulfillment states. */
final class CommerceCommercialStatusResolver {
    private const SUCCESSFUL_PAYMENTS = ['paid', 'captured', 'completed', 'succeeded', 'success'];
    private const FAILED_PAYMENTS = ['failed', 'declined', 'rejected', 'error'];
    private const REFUNDED_PAYMENTS = ['refunded', 'partially_refunded'];
    private const COMPLETED_FULFILLMENTS = ['fulfilled', 'completed', 'delivered', 'success'];
    private const FAILED_FULFILLMENTS = ['failed', 'error'];

    public function resolve(string $purchasestatus, array $paymentstatuses, array $fulfillmentstatuses): string {
        $purchase = $this->normalise($purchasestatus);
        $payments = array_map([$this, 'normalise'], $paymentstatuses);
        $fulfillments = array_map([$this, 'normalise'], $fulfillmentstatuses);

        if (in_array($purchase, ['replaced', 'superseded'], true)) {
            return CommerceCommercialStatus::REPLACED;
        }
        if (in_array($purchase, ['cancelled', 'canceled'], true)) {
            return CommerceCommercialStatus::CANCELLED;
        }
        if ($this->contains_any($payments, self::REFUNDED_PAYMENTS) || $purchase === 'refunded') {
            return CommerceCommercialStatus::REFUNDED;
        }
        if ($this->contains_any($payments, self::FAILED_PAYMENTS) || $purchase === 'failed') {
            return CommerceCommercialStatus::PAYMENT_FAILED;
        }

        $paid = $this->contains_any($payments, self::SUCCESSFUL_PAYMENTS)
            || in_array($purchase, ['paid', 'captured', 'fulfilled', 'completed', 'fulfillment_pending'], true);

        if (!$paid) {
            return CommerceCommercialStatus::PENDING;
        }
        if ($fulfillments === []) {
            return CommerceCommercialStatus::PAID;
        }

        $completed = count(array_filter(
            $fulfillments,
            fn(string $status): bool => in_array($status, self::COMPLETED_FULFILLMENTS, true)
        ));

        if ($completed === count($fulfillments)) {
            return CommerceCommercialStatus::FULFILLED;
        }
        if ($completed > 0) {
            return CommerceCommercialStatus::PARTIALLY_FULFILLED;
        }
        if ($this->contains_any($fulfillments, self::FAILED_FULFILLMENTS)) {
            return CommerceCommercialStatus::TO_FULFILL;
        }
        return CommerceCommercialStatus::TO_FULFILL;
    }

    private function normalise(string $status): string {
        return strtolower(trim($status));
    }

    private function contains_any(array $statuses, array $needles): bool {
        return array_intersect($statuses, $needles) !== [];
    }
}
