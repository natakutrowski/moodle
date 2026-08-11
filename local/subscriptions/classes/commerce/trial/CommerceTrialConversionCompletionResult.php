<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

/** Result of consuming one successful Native Trial conversion. */
final class CommerceTrialConversionCompletionResult {
    public function __construct(
        private readonly bool $applicable,
        private readonly int $subscriptionsreplaced,
        private readonly int $rolesremoved
    ) {
    }

    public static function not_applicable(): self {
        return new self(false, 0, 0);
    }

    public static function completed(int $subscriptionsreplaced, int $rolesremoved): self {
        return new self(true, $subscriptionsreplaced, $rolesremoved);
    }

    public function is_applicable(): bool {
        return $this->applicable;
    }

    public function get_subscriptions_replaced(): int {
        return $this->subscriptionsreplaced;
    }

    public function get_roles_removed(): int {
        return $this->rolesremoved;
    }
}
