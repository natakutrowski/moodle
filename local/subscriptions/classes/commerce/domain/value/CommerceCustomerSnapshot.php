<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable customer data captured when a purchase is prepared.
 *
 * A later Moodle profile change must not rewrite the historical identity used
 * on the purchase, payment description or receipt.
 */
final class CommerceCustomerSnapshot {

    public function __construct(
        private readonly ?int $userid,
        ?string $email,
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $country = null,
        ?string $language = null,
        private readonly array $metadata = []
    ) {
        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception(
                'A Commerce customer user identifier must be positive.'
            );
        }

        $this->email = self::normalise_nullable($email);
        $this->firstname = self::normalise_nullable($firstname);
        $this->lastname = self::normalise_nullable($lastname);
        $this->country = self::normalise_country($country);
        $this->language = self::normalise_language($language);

        if ($this->email !== null && !validate_email($this->email)) {
            throw new \coding_exception(
                'A Commerce customer email is invalid.'
            );
        }

        if ($this->userid === null && $this->email === null) {
            throw new \coding_exception(
                'A Commerce customer snapshot requires a user identifier or an email address.'
            );
        }
    }

    private readonly ?string $email;
    private readonly ?string $firstname;
    private readonly ?string $lastname;
    private readonly ?string $country;
    private readonly ?string $language;

    public function get_user_id(): ?int {
        return $this->userid;
    }

    public function get_email(): ?string {
        return $this->email;
    }

    public function get_firstname(): ?string {
        return $this->firstname;
    }

    public function get_lastname(): ?string {
        return $this->lastname;
    }

    public function get_fullname(): ?string {
        $fullname = trim(
            implode(' ', array_filter([
                $this->firstname,
                $this->lastname,
            ], static fn(?string $value): bool => $value !== null))
        );

        return $fullname === '' ? null : $fullname;
    }

    public function get_country(): ?string {
        return $this->country;
    }

    public function get_language(): ?string {
        return $this->language;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }

    public function is_registered_user(): bool {
        return $this->userid !== null;
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
            'fullname' => $this->get_fullname(),
            'country' => $this->country,
            'language' => $this->language,
            'metadata' => $this->metadata,
        ];
    }

    private static function normalise_nullable(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $normalised = trim($value);
        return $normalised === '' ? null : $normalised;
    }

    private static function normalise_country(?string $country): ?string {
        $normalised = self::normalise_nullable($country);
        if ($normalised === null) {
            return null;
        }

        $normalised = strtoupper($normalised);
        if (preg_match('/^[A-Z]{2}$/', $normalised) !== 1) {
            throw new \coding_exception(
                'A Commerce customer country must be a two-letter ISO code.'
            );
        }

        return $normalised;
    }

    private static function normalise_language(?string $language): ?string {
        $normalised = self::normalise_nullable($language);
        if ($normalised === null) {
            return null;
        }

        $normalised = strtolower($normalised);
        if (preg_match('/^[a-z]{2,3}(?:[_-][a-z]{2})?$/', $normalised) !== 1) {
            throw new \coding_exception(
                'A Commerce customer language code is invalid.'
            );
        }

        return $normalised;
    }
}
