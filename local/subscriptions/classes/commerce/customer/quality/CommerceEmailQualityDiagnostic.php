<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\quality;

defined('MOODLE_INTERNAL') || die();

/** Immutable result of a lightweight email quality check. */
final class CommerceEmailQualityDiagnostic {
    public function __construct(
        public readonly string $email,
        public readonly string $status,
        public readonly ?string $suggestion = null,
        public readonly ?string $reason = null
    ) {}

    public function has_issue(): bool {
        return $this->status !== CommerceEmailQualityService::STATUS_OK;
    }
}
