<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\eligibility;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised customer eligibility rules stored in promotion metadata.
 *
 * J14F intentionally stores the rule set in metadatajson so the feature does
 * not require a database migration and remains backwards-compatible.
 */
final class CommercePromotionEligibilityRuleSet {
    public const METADATA_KEY = 'customereligibility';
    public const MODE_ALL = 'all';
    public const MODE_ANY = 'any';

    /**
     * @param string[] $ownedskus
     * @param string[] $notownedskus
     */
    public function __construct(
        private readonly bool $requireslogin,
        private readonly string $mode,
        private readonly array $ownedskus,
        private readonly array $notownedskus
    ) {
        if (!in_array($mode, [self::MODE_ALL, self::MODE_ANY], true)) {
            throw new \coding_exception('Unsupported promotion eligibility mode.');
        }
    }

    public static function from_metadata(array $metadata): self {
        $raw = $metadata[self::METADATA_KEY] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }

        return new self(
            !empty($raw['requireslogin']),
            in_array(($raw['mode'] ?? ''), [self::MODE_ALL, self::MODE_ANY], true)
                ? (string)$raw['mode']
                : self::MODE_ALL,
            self::normalise_skus($raw['ownedskus'] ?? []),
            self::normalise_skus($raw['notownedskus'] ?? [])
        );
    }

    /**
     * @param string[] $ownedskus
     * @param string[] $notownedskus
     */
    public static function create(
        bool $requireslogin,
        string $mode,
        array $ownedskus,
        array $notownedskus
    ): self {
        return new self(
            $requireslogin,
            in_array($mode, [self::MODE_ALL, self::MODE_ANY], true) ? $mode : self::MODE_ALL,
            self::normalise_skus($ownedskus),
            self::normalise_skus($notownedskus)
        );
    }

    public function is_empty(): bool {
        return !$this->requireslogin && $this->ownedskus === [] && $this->notownedskus === [];
    }

    public function requires_login(): bool {
        return $this->requireslogin || $this->ownedskus !== [] || $this->notownedskus !== [];
    }

    public function get_mode(): string {
        return $this->mode;
    }

    /** @return string[] */
    public function get_owned_skus(): array {
        return $this->ownedskus;
    }

    /** @return string[] */
    public function get_not_owned_skus(): array {
        return $this->notownedskus;
    }

    public function to_metadata(): array {
        return [
            'requireslogin' => $this->requireslogin,
            'mode' => $this->mode,
            'ownedskus' => $this->ownedskus,
            'notownedskus' => $this->notownedskus,
        ];
    }

    private static function normalise_skus(mixed $values): array {
        if (!is_array($values)) {
            return [];
        }

        $normalised = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }
            $sku = strtoupper(trim($value));
            if ($sku !== '') {
                $normalised[$sku] = $sku;
            }
        }
        return array_values($normalised);
    }
}
