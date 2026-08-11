<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\library;

defined('MOODLE_INTERNAL') || die();

/** Reliable validity period attached to a course access source. */
final class CommerceCourseAccessPeriod {
    public function __construct(
        public readonly ?int $validfrom,
        public readonly ?int $validuntil,
        public readonly bool $lifetime
    ) {
        if ($validfrom !== null && $validfrom <= 0) {
            throw new \coding_exception('A course access start timestamp must be positive.');
        }
        if ($validuntil !== null && $validuntil <= 0) {
            throw new \coding_exception('A course access end timestamp must be positive.');
        }
        if ($validfrom !== null && $validuntil !== null && $validuntil <= $validfrom) {
            throw new \coding_exception('A course access end timestamp must be later than its start.');
        }
        if ($lifetime && $validuntil !== null) {
            throw new \coding_exception('A lifetime course access cannot have an expiry timestamp.');
        }
    }

    public static function unknown(): self {
        return new self(null, null, false);
    }

    public function is_current(?int $now = null): bool {
        $now ??= time();
        if ($this->validfrom !== null && $this->validfrom > $now) {
            return false;
        }
        return $this->validuntil === null || $this->validuntil >= $now;
    }

    public function to_array(): array {
        return [
            'validfrom' => $this->validfrom,
            'validuntil' => $this->validuntil,
            'islifetime' => $this->lifetime,
        ];
    }
}
