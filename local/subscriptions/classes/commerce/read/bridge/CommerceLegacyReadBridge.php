<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\bridge;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\CommerceReadCoordinatorFactory;
use local_subscriptions\commerce\read\observability\CommerceReadObservation;
use local_subscriptions\commerce\read\observability\CommerceReadObserver;
use local_subscriptions\commerce\read\policy\CommerceReadDecision;

/** Bridges Legacy consumers to the I10C decision and observability layers. */
final class CommerceLegacyReadBridge {
    public function __construct(
        private readonly ?CommerceReadObserver $observer = null
    ) {
    }

    public function inspect(
        string $consumer,
        string $family,
        int $legacyid
    ): CommerceReadDecision {
        if ($legacyid <= 0) {
            throw new \InvalidArgumentException('A positive Legacy identifier is required.');
        }

        $startedat = hrtime(true);
        $decision = CommerceReadCoordinatorFactory::create()
            ->read_purchase($consumer, $family, $legacyid);

        ($this->observer ?? new CommerceReadObserver())->observe(
            new CommerceReadObservation(
                $consumer,
                $family,
                $legacyid,
                $decision->source,
                $decision->is_success(),
                $decision->shadowcompared,
                $decision->shadowseverity,
                (int)round((hrtime(true) - $startedat) / 1_000_000)
            )
        );

        return $decision;
    }

    public function require_available(
        string $consumer,
        string $family,
        int $legacyid
    ): CommerceReadDecision {
        $decision = $this->inspect($consumer, $family, $legacyid);

        if (!$decision->is_success()) {
            throw new \RuntimeException(sprintf(
                'Commerce read unavailable for %s #%d (%s).',
                $family,
                $legacyid,
                $consumer
            ));
        }

        return $decision;
    }
}
