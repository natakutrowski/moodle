<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;
use local_subscriptions\crm\intelligence\scoring\LeadScore;

/**
 * Immutable context supplied to recommendation generators.
 *
 * The context contains already collected and calculated intelligence data.
 * Generators must not execute SQL or collect unrelated data directly.
 *
 * Additional transversal data may progressively be added through the data
 * collection while the stable constructor remains compatible.
 */
final class RecommendationGenerationContext {

    /**
     * Additional structured context indexed by a stable technical key.
     *
     * @var array<string, mixed>
     */
    public readonly array $data;

    /**
     * @param CrmIntelligenceSnapshot $snapshot Existing CRM intelligence snapshot.
     * @param LeadScore $leadscore Existing historical Lead Score result.
     * @param array $opportunities Existing detected commercial opportunities.
     * @param int|null $userid Explicit user ID when available.
     * @param array $data Additional preloaded transversal information.
     * @param int|null $generatedat Reference timestamp for the generation run.
     */
    public function __construct(
        public readonly CrmIntelligenceSnapshot $snapshot,
        public readonly LeadScore $leadscore,
        public readonly array $opportunities = [],
        public readonly ?int $userid = null,
        array $data = [],
        public readonly ?int $generatedat = null
    ) {
        if ($this->userid !== null && $this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation generation user ID must be greater than zero.'
            );
        }

        if ($this->generatedat !== null && $this->generatedat <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation generation timestamp must be greater than zero.'
            );
        }

        $this->validate_opportunities($this->opportunities);
        $this->validate_data($data);

        $this->data = $data;
    }

    /**
     * Return the effective generation timestamp.
     */
    public function timestamp(): int {
        return $this->generatedat ?? time();
    }

    /**
     * Check whether additional context contains a value.
     */
    public function has(string $key): bool {
        return array_key_exists($key, $this->data);
    }

    /**
     * Read an optional additional context value.
     */
    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    /**
     * Read a required additional context value.
     *
     * @throws \UnexpectedValueException When the key is unavailable.
     */
    public function require(string $key): mixed {
        if (!$this->has($key)) {
            throw new \UnexpectedValueException(
                'Missing recommendation generation context value: ' . $key
            );
        }

        return $this->data[$key];
    }

    /**
     * Return the effective user ID when it can be resolved.
     *
     * The explicit ID takes precedence. The method then checks common snapshot
     * properties without requiring collectors or generators to duplicate the
     * resolution logic.
     */
    public function resolved_userid(): ?int {
        if ($this->userid !== null) {
            return $this->userid;
        }

        foreach (['userid', 'userId', 'id'] as $property) {
            if (
                property_exists($this->snapshot, $property) &&
                is_numeric($this->snapshot->{$property}) &&
                (int)$this->snapshot->{$property} > 0
            ) {
                return (int)$this->snapshot->{$property};
            }
        }

        return null;
    }

    /**
     * Build a recommendation target for the current user.
     */
    public function user_target(): ?RecommendationTarget {
        $userid = $this->resolved_userid();

        if ($userid === null) {
            return null;
        }

        return new RecommendationTarget(
            RecommendationTarget::USER,
            $userid
        );
    }

    /**
     * Return a new context containing additional preloaded data.
     *
     * Existing values are preserved unless explicitly replaced.
     */
    public function with_data(array $data): self {
        $merged = array_replace($this->data, $data);

        return new self(
            $this->snapshot,
            $this->leadscore,
            $this->opportunities,
            $this->userid,
            $merged,
            $this->generatedat
        );
    }

    /**
     * Validate existing opportunity objects.
     */
    private function validate_opportunities(array $opportunities): void {
        foreach ($opportunities as $opportunity) {
            if (!is_object($opportunity)) {
                throw new \InvalidArgumentException(
                    'Recommendation opportunities must contain objects.'
                );
            }
        }
    }

    /**
     * Validate top-level context keys.
     *
     * Values are intentionally not restricted to scalars because the context
     * may contain immutable DTOs assembled by dedicated services.
     */
    private function validate_data(array $data): void {
        foreach ($data as $key => $value) {
            if (
                !is_string($key) ||
                preg_match('/^[a-z][a-z0-9_.]{1,99}$/', $key) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Recommendation generation context keys must be stable technical strings.'
                );
            }

            if (is_resource($value)) {
                throw new \InvalidArgumentException(
                    'Recommendation generation context cannot contain resources.'
                );
            }
        }
    }
}