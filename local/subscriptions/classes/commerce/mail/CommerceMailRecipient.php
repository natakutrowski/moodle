<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Recipient resolved independently from a Moodle user account.
 */
final class CommerceMailRecipient {

    public function __construct(
        private readonly string $email,
        private readonly string $name = '',
        private readonly ?int $userid = null
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \coding_exception(
                'A Commerce transactional mail recipient requires a valid email address.'
            );
        }

        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception(
                'A Commerce transactional mail recipient user ID must be positive.'
            );
        }
    }

    public function get_email(): string {
        return $this->email;
    }

    public function get_name(): string {
        return trim($this->name);
    }

    public function get_user_id(): ?int {
        return $this->userid;
    }

    public function is_guest(): bool {
        return $this->userid === null;
    }
}
