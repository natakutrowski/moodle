<?php

namespace local_subscriptions\commerce\payment;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent customer identity for a Commerce payment.
 */
final class CommercePaymentCustomer {

    public function __construct(
        private readonly ?int $userid,
        private readonly string $email,
        private readonly ?string $firstname = null,
        private readonly ?string $lastname = null,
        private readonly array $metadata = []
    ) {
        if (
            $userid !== null
            && $userid <= 0
        ) {
            throw new \coding_exception(
                'A Commerce payment customer user identifier must be positive.'
            );
        }

        $email = trim($email);

        if (
            $email === ''
            || !validate_email($email)
        ) {
            throw new \coding_exception(
                'A Commerce payment customer must have a valid email address.'
            );
        }
    }

    public function get_user_id(): ?int {
        return $this->userid;
    }

    public function get_email(): string {
        return \core_text::strtolower(
            trim($this->email)
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
        $parts = array_values(
            array_filter(
                [
                    $this->get_first_name(),
                    $this->get_last_name(),
                ],
                static fn(?string $value): bool =>
                    $value !== null
            )
        );

        return $parts !== []
            ? implode(' ', $parts)
            : null;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_authenticated(): bool {
        return $this->userid !== null;
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