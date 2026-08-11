<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

/** Result of one explicit cart mutation. */
final class CommerceCartOperationResult {
    /** @param CommerceCartMessage[] $messages */
    public function __construct(
        private readonly CommerceCart $cart,
        private readonly bool $changed,
        private readonly array $messages = []
    ) {
        foreach ($messages as $message) {
            if (!$message instanceof CommerceCartMessage) {
                throw new \coding_exception('Invalid Commerce cart message collection.');
            }
        }
    }

    public function get_cart(): CommerceCart {
        return $this->cart;
    }

    public function has_changed(): bool {
        return $this->changed;
    }

    /** @return CommerceCartMessage[] */
    public function get_messages(): array {
        return $this->messages;
    }
}
