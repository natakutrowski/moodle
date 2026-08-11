<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Customer-facing access action attached to one purchased item. */
final class CommerceOrderAccessPresentation {
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $status,
        public readonly bool $available,
        public readonly ?string $url,
        public readonly string $grantreference,
        public readonly ?int $validuntil = null,
        public readonly array $metadata = [],
        public readonly ?string $unavailablereason = null
    ) {
    }
}
