<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalOperationalHealthService {
    public function __construct(
        private readonly PayPalGatewayConfiguration $configuration
    ) {}

    public static function create(): self {
        return new self(new PayPalGatewayConfiguration());
    }

    public function inspect(bool $checkremote = false): PayPalOperationalHealth {
        $credentialsconfigured = $this->configuration->is_configured();
        $webhookconfigured = $this->configuration->get_webhook_id() !== null;
        $oauthreachable = false;
        $oautherror = null;

        if ($checkremote && $credentialsconfigured) {
            try {
                (new PayPalRestPaymentGateway($this->configuration))->test_connection();
                $oauthreachable = true;
            } catch (\Throwable $exception) {
                $oautherror = $exception->getMessage();
            }
        } else if ($credentialsconfigured) {
            $oauthreachable = true;
        }

        return new PayPalOperationalHealth(
            $this->configuration->get_environment(),
            $credentialsconfigured,
            $webhookconfigured,
            $oauthreachable,
            $oautherror,
            $credentialsconfigured && $oauthreachable,
            $credentialsconfigured && $webhookconfigured
        );
    }
}
