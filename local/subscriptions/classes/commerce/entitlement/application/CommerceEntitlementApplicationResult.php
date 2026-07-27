<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\application;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Normalized result of applying one Native Commerce entitlement grant.
 */
final class CommerceEntitlementApplicationResult {
    public const STATUS_APPLIED = 'applied';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly CommerceEntitlementGrant $grant,
        private readonly string $status,
        private readonly array $payload = [],
        private readonly ?string $message = null
    ) {
        if (!in_array($status, [self::STATUS_APPLIED, self::STATUS_FAILED], true)) {
            throw new \coding_exception('Invalid Commerce entitlement application result status.');
        }
    }

    public function get_grant(): CommerceEntitlementGrant {
        return $this->grant;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function is_applied(): bool {
        return $this->status === self::STATUS_APPLIED;
    }

    public function get_payload(): array {
        return $this->payload;
    }

    public function get_message(): ?string {
        return $this->message;
    }
}
