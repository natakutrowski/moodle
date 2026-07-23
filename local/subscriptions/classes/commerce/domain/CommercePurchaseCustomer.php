<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Customer associated with a Commerce purchase.
 *
 * A customer can be a registered Moodle user or a guest identified only by
 * an email address.
 */
final class CommercePurchaseCustomer {

    /**
     * @param int|null $userid Moodle user identifier.
     * @param string|null $email Customer email.
     * @param string|null $firstname Customer first name.
     * @param string|null $lastname Customer last name.
     * @param string|null $country ISO country code.
     * @param string|null $language Preferred language.
     */
    public function __construct(
        private readonly ?int $userid,
        private readonly ?string $email,
        private readonly ?string $firstname = null,
        private readonly ?string $lastname = null,
        private readonly ?string $country = null,
        private readonly ?string $language = null
    ) {
        if (
            $userid !== null
            && $userid <= 0
        ) {
            throw new \coding_exception(
                'A Commerce customer user identifier must be positive.'
            );
        }

        if (
            $email !== null
            && trim($email) === ''
        ) {
            throw new \coding_exception(
                'A Commerce customer email cannot be empty.'
            );
        }

        if (
            $email !== null
            && !validate_email(
                trim($email)
            )
        ) {
            throw new \coding_exception(
                'A Commerce customer email is invalid.'
            );
        }
    }

    /**
     * Build a customer from a Commerce purchase.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return self
     */
    public static function from_purchase(
        CommercePurchase $purchase
    ): self {
        return new self(
            $purchase->get_user_id(),
            self::normalise_nullable_string(
                $purchase->get_customer_email()
            ),
            self::normalise_metadata_string(
                $purchase,
                'firstname'
            ),
            self::normalise_metadata_string(
                $purchase,
                'lastname'
            ),
            self::normalise_metadata_string(
                $purchase,
                'country'
            ),
            self::normalise_metadata_string(
                $purchase,
                'language'
            )
        );
    }

    /**
     * Return the Moodle user identifier.
     *
     * @return int|null
     */
    public function get_user_id(): ?int {
        return $this->userid;
    }

    /**
     * Return the customer email.
     *
     * @return string|null
     */
    public function get_email(): ?string {
        return self::normalise_nullable_string(
            $this->email
        );
    }

    /**
     * Return the customer first name.
     *
     * @return string|null
     */
    public function get_firstname(): ?string {
        return self::normalise_nullable_string(
            $this->firstname
        );
    }

    /**
     * Return the customer last name.
     *
     * @return string|null
     */
    public function get_lastname(): ?string {
        return self::normalise_nullable_string(
            $this->lastname
        );
    }

    /**
     * Return the customer country.
     *
     * @return string|null
     */
    public function get_country(): ?string {
        $country =
            self::normalise_nullable_string(
                $this->country
            );

        if ($country === null) {
            return null;
        }

        return strtoupper(
            $country
        );
    }

    /**
     * Return the preferred language.
     *
     * @return string|null
     */
    public function get_language(): ?string {
        $language =
            self::normalise_nullable_string(
                $this->language
            );

        if ($language === null) {
            return null;
        }

        return strtolower(
            $language
        );
    }

    /**
     * Whether the customer has at least one usable identity.
     *
     * @return bool
     */
    public function has_identity(): bool {
        return $this->get_user_id() !== null
            || $this->get_email() !== null;
    }

    /**
     * Whether the customer is linked to a registered Moodle user.
     *
     * @return bool
     */
    public function is_registered_user(): bool {
        return $this->get_user_id() !== null;
    }

    /**
     * Whether the customer is identified only as a guest.
     *
     * @return bool
     */
    public function is_guest(): bool {
        return $this->get_user_id() === null
            && $this->get_email() !== null;
    }

    /**
     * Return the customer's display name.
     *
     * @return string|null
     */
    public function get_display_name(): ?string {
        $parts =
            array_filter(
                [
                    $this->get_firstname(),
                    $this->get_lastname(),
                ],
                static fn(?string $value): bool =>
                    $value !== null
            );

        if ($parts !== []) {
            return implode(
                ' ',
                $parts
            );
        }

        return $this->get_email();
    }

    /**
     * Export the customer.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'userid' =>
                $this->get_user_id(),

            'email' =>
                $this->get_email(),

            'firstname' =>
                $this->get_firstname(),

            'lastname' =>
                $this->get_lastname(),

            'display_name' =>
                $this->get_display_name(),

            'country' =>
                $this->get_country(),

            'language' =>
                $this->get_language(),

            'registered_user' =>
                $this->is_registered_user(),

            'guest' =>
                $this->is_guest(),
        ];
    }

    /**
     * Read a string from purchase metadata.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @param string $key Metadata key.
     * @return string|null
     */
    private static function normalise_metadata_string(
        CommercePurchase $purchase,
        string $key
    ): ?string {
        $value =
            $purchase->get_metadata_value(
                $key
            );

        if (
            $value === null
            || !is_scalar($value)
        ) {
            return null;
        }

        return self::normalise_nullable_string(
            (string)$value
        );
    }

    /**
     * Normalise an optional string.
     *
     * @param string|null $value Value.
     * @return string|null
     */
    private static function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}