<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable public reference of a Commerce purchase.
 *
 * It is safe to display in URLs, emails, receipts and support interfaces. It
 * remains distinct from both the opaque domain identifier and provider-side
 * transaction references.
 */
final class CommercePurchaseReference {

    private const PREFIX = 'cmp_';
    private const TOKEN_LENGTH = 24;

    private function __construct(
        private readonly string $value
    ) {
    }

    /**
     * Derive a stable public reference from a native purchase identity.
     *
     * @param CommercePurchaseId $id Purchase identity.
     * @return self
     */
    public static function from_purchase_id(
        CommercePurchaseId $id
    ): self {
        return new self(
            self::PREFIX . substr(
                hash('sha256', $id->get_value()),
                0,
                self::TOKEN_LENGTH
            )
        );
    }

    /**
     * Restore a reference from its canonical representation.
     *
     * @param string $value Reference.
     * @return self
     */
    public static function from_string(string $value): self {
        $normalised = strtolower(trim($value));
        $pattern = '/^' . preg_quote(self::PREFIX, '/')
            . '[a-f0-9]{' . self::TOKEN_LENGTH . '}$/';

        if (preg_match($pattern, $normalised) !== 1) {
            throw new \coding_exception(
                'A Commerce purchase reference must use the canonical cmp_ format.'
            );
        }

        return new self($normalised);
    }

    public function get_value(): string {
        return $this->value;
    }

    public function equals(self $other): bool {
        return hash_equals(
            $this->value,
            $other->value
        );
    }

    public function __toString(): string {
        return $this->value;
    }

    /** @return array<string, string> */
    public function to_array(): array {
        return ['value' => $this->value];
    }
}
