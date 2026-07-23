<?php

namespace local_subscriptions\commerce\purchase;

defined('MOODLE_INTERNAL') || die();

/**
 * Customer identity used during a Commerce purchase workflow.
 *
 * A customer may be an authenticated Moodle user or a guest identified
 * only by an email address.
 */
final class CommerceCustomer {

    public function __construct(
        private readonly ?int $userid,
        private readonly string $email,
        private readonly ?string $firstname = null,
        private readonly ?string $lastname = null,
        private readonly array $metadata = []
    ) {
        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception(
                'A Commerce customer user identifier must be positive.'
            );
        }

        $email = trim($email);

        if (
            $email === ''
            || !validate_email($email)
        ) {
            throw new \coding_exception(
                'A Commerce customer must have a valid email address.'
            );
        }
    }

    public function get_user_id(): ?int {
        return $this->userid;
    }

    public function get_email(): string {
        return trim(
            \core_text::strtolower(
                $this->email
            )
        );
    }

    public function get_first_name(): ?string {
        return $this->normalise_nullable_string(
            $this->firstname
        );
    }

    public function get_last_name(): ?string {
        return $this->normalise_nullable_string(
            $this->lastname
        );
    }

    public function get_full_name(): ?string {
        $parts = array_filter(
            [
                $this->get_first_name(),
                $this->get_last_name(),
            ],
            static fn(?string $value): bool =>
                $value !== null
        );

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function is_authenticated(): bool {
        return $this->userid !== null;
    }

    public function is_guest(): bool {
        return !$this->is_authenticated();
    }

    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}