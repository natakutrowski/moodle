<?php

declare(strict_types=1);

namespace local_subscriptions\payment\alfa\callback;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\alfa\AlfaGateway;
use local_subscriptions\payment\dto\InternalEvent;

/**
 * Revalidates every callback against Alfa's authoritative order-status API.
 */
final class AlfaGatewayCallbackStatusProbe implements AlfaCallbackStatusProbeInterface {
    public function probe(array $identity, array $headers = []): InternalEvent {
        $gateway = new AlfaGateway();

        return $gateway->parse_webhook(
            json_encode($identity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $headers
        );
    }
}
