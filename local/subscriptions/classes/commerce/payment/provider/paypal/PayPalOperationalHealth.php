<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalOperationalHealth {
    public function __construct(
        public readonly string $environment,
        public readonly bool $credentialsconfigured,
        public readonly bool $webhookconfigured,
        public readonly bool $oauthreachable,
        public readonly ?string $oautherror,
        public readonly bool $readyforcheckout,
        public readonly bool $readyforwebhooks
    ) {}

    public function is_healthy(): bool {
        return $this->readyforcheckout && $this->readyforwebhooks;
    }
}
