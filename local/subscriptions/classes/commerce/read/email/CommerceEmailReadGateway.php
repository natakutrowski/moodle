<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\email;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\bridge\CommerceLegacyReadBridge;
use local_subscriptions\commerce\read\policy\CommerceReadConsumer;
use local_subscriptions\commerce\read\policy\CommerceReadDecision;

/** Native-aware validation used before building Legacy-compatible emails. */
final class CommerceEmailReadGateway {
    public function __construct(
        private readonly ?CommerceLegacyReadBridge $bridge = null
    ) {
    }

    public function inspect_subscription(int $subscriptionid): CommerceReadDecision {
        return $this->inspect('subscription', $subscriptionid);
    }

    public function inspect_digital_purchase(int $purchaseid): CommerceReadDecision {
        return $this->inspect('digital', $purchaseid);
    }

    private function inspect(string $family, int $legacyid): CommerceReadDecision {
        return ($this->bridge ?? new CommerceLegacyReadBridge())->require_available(
            CommerceReadConsumer::EMAIL,
            $family,
            $legacyid
        );
    }
}
