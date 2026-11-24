<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G1/G2A: fail-closed, non-wired feature switch.
 * A config setting alone MUST NOT enable Gift Commerce before the G2B/G3 gates.
 */
final class CommerceGiftFeatureToggle {
    public const RUNTIME_WIRED = true;
    public const CONFIG_ENABLED = 'commerce_gifts_enabled';
    public const CONFIG_ISSUE = 'commerce_gifts_issue_enabled';
    public const CONFIG_REDEEM = 'commerce_gifts_redeem_enabled';

    public function __construct(private readonly ?bool $configuredoverride = null) {
    }

    public function is_configured(): bool {
        return $this->configuredoverride ?? (bool)get_config('local_subscriptions', self::CONFIG_ENABLED);
    }

    public function is_enabled(): bool {
        return CommerceGiftCommercialRuntimeGate::can_operate()
            && $this->is_configured();
    }

    public function can_issue(): bool {
        return $this->is_enabled()
            && (bool)get_config('local_subscriptions', self::CONFIG_ISSUE);
    }

    public function can_redeem(): bool {
        return $this->is_enabled()
            && (bool)get_config('local_subscriptions', self::CONFIG_REDEEM);
    }
}
