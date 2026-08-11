<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\navigation;

defined('MOODLE_INTERNAL') || die();

/** Stable identifier for a product exposed by the federated catalogue. */
final class CommerceCatalogIdentity {
    private const ALLOWED_ORIGINS = [
        'native',
        'legacy_plan',
        'legacy_digital',
    ];

    public function __construct(
        private readonly string $origin,
        private readonly int $id
    ) {
        if (!in_array($origin, self::ALLOWED_ORIGINS, true)) {
            throw new \InvalidArgumentException('Unsupported catalogue origin: ' . $origin);
        }
        if ($id <= 0) {
            throw new \InvalidArgumentException('The catalogue product identifier must be positive.');
        }
    }

    public function get_origin(): string {
        return $this->origin;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function to_string(): string {
        return $this->origin . ':' . $this->id;
    }

    public static function from_string(string $value): ?self {
        if (!preg_match('/^(native|legacy_plan|legacy_digital):([1-9][0-9]*)$/', trim($value), $matches)) {
            return null;
        }

        return new self($matches[1], (int)$matches[2]);
    }

    /**
     * Resolve the new compact identity and the transitional origin/id format.
     *
     * An id-only historical URL is interpreted as a Native product URL.
     */
    public static function from_request(string $catalogkey, string $origin, int $id): ?self {
        if ($catalogkey !== '') {
            return self::from_string($catalogkey);
        }

        if ($id <= 0) {
            return null;
        }

        $resolvedorigin = $origin !== '' ? $origin : 'native';
        try {
            return new self($resolvedorigin, $id);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
