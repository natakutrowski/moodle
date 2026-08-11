<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Complete Native customer order read model shared by post-payment surfaces. */
final class CommerceOrderPresentation {
    public function __construct(
        public readonly int $purchaseid,
        public readonly string $uuid,
        public readonly string $reference,
        public readonly string $type,
        public readonly ?int $userid,
        public readonly string $customeremail,
        public readonly string $currency,
        public readonly int $totalminor,
        public readonly string $status,
        public readonly string $paymentstatus,
        public readonly string $fulfillmentstatus,
        public readonly ?string $provider,
        public readonly ?int $paidat,
        public readonly int $timecreated,
        public readonly array $items,
        public readonly array $timeline,
        public readonly array $actions = [],
        public readonly array $metadata = [],
        public readonly ?CommerceOrderPaymentPresentation $payment = null
    ) {
    }

    public function is_paid(): bool {
        return in_array($this->paymentstatus, ['paid', 'captured', 'completed', 'succeeded', 'success'], true);
    }

    public function has_available_accesses(): bool {
        foreach ($this->items as $item) {
            foreach ($item->accesses as $access) {
                if ($access->available) {
                    return true;
                }
            }
        }
        return false;
    }
}
