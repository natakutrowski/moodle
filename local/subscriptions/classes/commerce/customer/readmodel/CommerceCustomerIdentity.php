<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Canonical customer identity shared by Moodle users and Guest purchases. */
final class CommerceCustomerIdentity {
    /** @param string[] $customerids */
    public function __construct(
        public readonly ?int $userid,
        public readonly ?string $email,
        public readonly ?string $firstname = null,
        public readonly ?string $lastname = null,
        public readonly array $customerids = [],
        public readonly bool $hasguesthistory = false
    ) {
        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception('A Commerce customer user identifier must be positive.');
        }
        if ($userid === null && trim((string)$email) === '') {
            throw new \coding_exception('A Commerce customer identity requires a user identifier or an email.');
        }
    }

    public function is_guest(): bool {
        return $this->userid === null;
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'userid' => $this->userid,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'customerids' => $this->customerids,
            'isguest' => $this->is_guest(),
            'hasguesthistory' => $this->hasguesthistory,
        ];
    }
}
