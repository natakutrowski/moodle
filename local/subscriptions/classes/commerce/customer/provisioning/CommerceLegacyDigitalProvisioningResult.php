<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\provisioning;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of one Legacy Digital account provisioning.
 */
final class CommerceLegacyDigitalProvisioningResult {
    public function __construct(
        public readonly string $email,
        public readonly string $status,
        public readonly ?int $userid = null,
        public readonly int $legacypurchaseslinked = 0,
        public readonly int $nativepurchasesreconciled = 0,
        public readonly ?int $mailqueueid = null,
        public readonly ?string $error = null
    ) {
    }
}
