<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentProviderOperationalStatus {
    public function __construct(
        public readonly string $provider,
        public readonly string $environment,
        public readonly bool $configured,
        public readonly bool $webhookconfigured,
        public readonly bool $refundconfigured,
        public readonly bool $adminallowed,
        public readonly array $details = []
    ) {
    }
}
