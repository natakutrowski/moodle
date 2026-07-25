<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\observability;

defined('MOODLE_INTERNAL') || die();

/**
 * Reports only anomalous Commerce read observations.
 *
 * A successful Legacy fallback is expected while a consumer's Native read
 * feature flag remains disabled. It must therefore stay silent and must not
 * leak a Moodle developer debugging message into an Admin page.
 */
final class CommerceReadObserver {
    public function observe(CommerceReadObservation $observation): void {
        if (!$this->should_log($observation)) {
            return;
        }

        debugging(
            '[I10C read] ' . json_encode([
                'consumer' => $observation->consumer,
                'family' => $observation->family,
                'legacyid' => $observation->legacyid,
                'source' => $observation->source,
                'success' => $observation->success,
                'shadowcompared' => $observation->shadowcompared,
                'shadowseverity' => $observation->shadowseverity,
                'durationms' => $observation->durationms,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            DEBUG_DEVELOPER
        );
    }

    private function should_log(CommerceReadObservation $observation): bool {
        if (!$observation->success) {
            return true;
        }

        return in_array(
            $observation->shadowseverity,
            ['warning', 'critical'],
            true
        );
    }
}
