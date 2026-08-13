<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\payment\dto\InternalEvent;
final class StripePaymentProviderStatus {
    public function __construct(
        public readonly string $sessionid,
        public readonly string $profile,
        public readonly string $checkoutstatus,
        public readonly string $paymentstatus,
        public readonly ?int $amountminor,
        public readonly ?string $currency,
        public readonly InternalEvent $event
    ) {}
    public function is_paid(): bool {
        return strtolower($this->checkoutstatus) === 'complete'
            && strtolower($this->paymentstatus) === 'paid';
    }
}
