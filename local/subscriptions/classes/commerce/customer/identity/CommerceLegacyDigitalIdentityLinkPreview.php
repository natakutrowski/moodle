<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only cross-source link preview.
 */
final class CommerceLegacyDigitalIdentityLinkPreview {
    public function __construct(
        public readonly string $legacyemail,
        public readonly string $legacyfirstname,
        public readonly string $legacylastname,
        public readonly int $targetuserid,
        public readonly string $targetemail,
        public readonly string $targetfullname,
        public readonly int $legacypurchases,
        public readonly int $similarityscore,
        public readonly array $reasons
    ) {
    }

    public function can_execute(): bool {
        return $this->legacypurchases > 0
            && $this->targetuserid > 1
            && $this->similarityscore >= 60;
    }
}
