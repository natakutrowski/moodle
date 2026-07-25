<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

/**
 * Native opaque identity of a Commerce purchase.
 *
 * This identity is independent of the purchased product type, payment
 * provider and any Legacy persistence identifier. The hexadecimal format is
 * deliberately storage-neutral and can be generated before a purchase is
 * persisted.
 */
final class CommercePurchaseId {

    /** Number of random bytes used by generated identifiers. */
    private const RANDOM_BYTES = 16;

    /** Canonical identifier length after hexadecimal encoding. */
    private const HEX_LENGTH = self::RANDOM_BYTES * 2;

    private function __construct(
        private readonly string $value
    ) {
    }

    /**
     * Generate a cryptographically random purchase identifier.
     *
     * @return self
     */
    public static function generate(): self {
        return new self(
            bin2hex(random_bytes(self::RANDOM_BYTES))
        );
    }

    /**
     * Restore an identifier from its canonical representation.
     *
     * @param string $value Identifier.
     * @return self
     */
    public static function from_string(string $value): self {
        $normalised = strtolower(trim($value));

        if (
            strlen($normalised) !== self::HEX_LENGTH
            || !ctype_xdigit($normalised)
        ) {
            throw new \coding_exception(
                'A Commerce purchase identifier must contain exactly 32 hexadecimal characters.'
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
