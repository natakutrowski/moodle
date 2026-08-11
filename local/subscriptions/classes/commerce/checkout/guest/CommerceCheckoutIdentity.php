<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Resolved authenticated or provisional identity used by Unified Checkout. */
final class CommerceCheckoutIdentity {
    public function __construct(
        public readonly int $userid,
        public readonly string $email,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly ?CommerceGuestCheckoutSession $guestsession = null
    ) {}

    public function is_guest_checkout(): bool {
        return $this->guestsession !== null;
    }
}
