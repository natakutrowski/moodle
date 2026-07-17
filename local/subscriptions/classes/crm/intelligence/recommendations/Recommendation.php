<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable recommendation produced by CRM intelligence.
 *
 * The constructor preserves the four original parameters so that existing
 * intelligence code remains compatible while Phase 7 progressively adopts
 * targets, evidence, sources, lifecycle states and multiple actions.
 */
final class Recommendation {

    /**
     * Existing presentation type used by current renderers.
     */
    public const PRESENTATION_INFO = 'info';

    /**
     * Existing presentation type used for visual warnings.
     */
    public const PRESENTATION_WARNING = 'warning';

    /**
     * Existing presentation type used for actionable recommendations.
     */
    public const PRESENTATION_ACTION = 'action';

    /**
     * All proposed actions.
     *
     * The legacy primary action is included in this collection when present.
     *
     * @var RecommendationAction[]
     */
    public readonly array $actions;

    /**
     * All structured evidence supporting the recommendation.
     *
     * @var RecommendationEvidence[]
     */
    public readonly array $evidence;

    /**
     * All contributing recommendation sources.
     *
     * @var string[]
     */
    public readonly array $sources;

    /**
     * Normalized operational priority level.
     */
    public readonly string $prioritylevel;

    /**
     * @param string $key Stable recommendation key.
     * @param string $type Legacy presentation type: info, warning or action.
     * @param int $priority Numerical priority score from 0 to 100.
     * @param RecommendationAction|null $action Legacy primary action.
     * @param string $recommendationtype Functional RecommendationType value.
     * @param string $status RecommendationStatus value.
     * @param RecommendationTarget|null $target Target CRM entity.
     * @param RecommendationEvidence[] $evidence Supporting evidence.
     * @param RecommendationAction[] $actions Additional proposed actions.
     * @param string[] $sources Contributing RecommendationSource values.
     * @param int|null $createdat Recommendation creation timestamp.
     * @param int|null $validuntil Optional validity timestamp.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type = self::PRESENTATION_INFO,
        public readonly int $priority = 50,
        public readonly ?RecommendationAction $action = null,
        public readonly string $recommendationtype = RecommendationType::OPERATIONAL_REVIEW,
        public readonly string $status = RecommendationStatus::PROPOSED,
        public readonly ?RecommendationTarget $target = null,
        array $evidence = [],
        array $actions = [],
        array $sources = [],
        public readonly ?int $createdat = null,
        public readonly ?int $validuntil = null
    ) {
        if (!$this->is_valid_key($this->key)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation key.'
            );
        }

        if (!self::is_valid_presentation_type($this->type)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation presentation type.'
            );
        }

        if (!RecommendationPriority::is_valid_score($this->priority)) {
            throw new \InvalidArgumentException(
                'Recommendation priority must be between 0 and 100.'
            );
        }

        if (!RecommendationType::is_valid($this->recommendationtype)) {
            throw new \InvalidArgumentException(
                'Invalid functional recommendation type.'
            );
        }

        if (!RecommendationStatus::is_valid($this->status)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation status.'
            );
        }

        if ($this->createdat !== null && $this->createdat <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation creation timestamp must be greater than zero.'
            );
        }

        if ($this->validuntil !== null && $this->validuntil <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation validity timestamp must be greater than zero.'
            );
        }

        if (
            $this->createdat !== null &&
            $this->validuntil !== null &&
            $this->validuntil <= $this->createdat
        ) {
            throw new \InvalidArgumentException(
                'Recommendation validity timestamp must be later than its creation timestamp.'
            );
        }

        $this->prioritylevel = RecommendationPriority::from_score(
            $this->priority
        );

        $this->evidence = $this->normalize_evidence($evidence);
        $this->actions = $this->normalize_actions($this->action, $actions);
        $this->sources = $this->normalize_sources(
            $sources,
            $this->evidence
        );
    }

    /**
     * Return the preferred action.
     *
     * Compatibility rule:
     * - first use the original legacy action;
     * - otherwise use an action explicitly marked as primary;
     * - otherwise use the first proposed action.
     */
    public function primary_action(): ?RecommendationAction {
        if ($this->action !== null) {
            return $this->action;
        }

        foreach ($this->actions as $action) {
            if ($action->primary) {
                return $action;
            }
        }

        return $this->actions[0] ?? null;
    }

    /**
     * Check whether this recommendation is currently active.
     */
    public function is_active(): bool {
        if (!RecommendationStatus::is_active($this->status)) {
            return false;
        }

        if ($this->validuntil !== null && $this->validuntil <= time()) {
            return false;
        }

        return true;
    }

    /**
     * Check whether this recommendation has reached a terminal status.
     */
    public function is_terminal(): bool {
        return RecommendationStatus::is_terminal($this->status);
    }

    /**
     * Stable target identity.
     */
    public function target_identity(): ?string {
        return $this->target?->identity();
    }

    /**
     * Stable fingerprint used later for recommendation deduplication.
     *
     * The recommendation key and target identify the operational situation.
     * Evidence values are deliberately excluded because they may evolve while
     * referring to the same underlying recommendation.
     */
    public function fingerprint(): string {
        $targetidentity = $this->target_identity() ?? 'global';

        return hash(
            'sha256',
            $this->recommendationtype . '|' . $this->key . '|' . $targetidentity
        );
    }

    /**
     * Return whether the recommendation includes evidence from a source.
     */
    public function has_source(string $source): bool {
        return in_array($source, $this->sources, true);
    }

    /**
     * Serialize the recommendation for renderers, APIs and persistence DTOs.
     */
    public function to_object(): \stdClass {
        $primaryaction = $this->primary_action();

        return (object)[
            'key' => $this->key,

            // Existing fields kept for backward compatibility.
            'type' => $this->type,
            'priority' => $this->priority,
            'action' => $primaryaction?->to_object(),

            // Phase 7 fields.
            'recommendationtype' => $this->recommendationtype,
            'prioritylevel' => $this->prioritylevel,
            'status' => $this->status,
            'target' => $this->target?->to_object(),
            'targetidentity' => $this->target_identity(),
            'fingerprint' => $this->fingerprint(),
            'evidence' => array_map(
                static fn(RecommendationEvidence $item): \stdClass => $item->to_object(),
                $this->evidence
            ),
            'actions' => array_map(
                static fn(RecommendationAction $item): \stdClass => $item->to_object(),
                $this->actions
            ),
            'sources' => $this->sources,
            'createdat' => $this->createdat,
            'validuntil' => $this->validuntil,
            'active' => $this->is_active(),
        ];
    }

    /**
     * Return all supported legacy presentation types.
     *
     * @return string[]
     */
    public static function presentation_types(): array {
        return [
            self::PRESENTATION_INFO,
            self::PRESENTATION_WARNING,
            self::PRESENTATION_ACTION,
        ];
    }

    /**
     * Check whether a presentation type is supported.
     */
    public static function is_valid_presentation_type(string $type): bool {
        return in_array($type, self::presentation_types(), true);
    }

    /**
     * Validate and deduplicate evidence.
     *
     * @param array $evidence
     * @return RecommendationEvidence[]
     */
    private function normalize_evidence(array $evidence): array {
        $normalized = [];

        foreach ($evidence as $item) {
            if (!$item instanceof RecommendationEvidence) {
                throw new \InvalidArgumentException(
                    'Recommendation evidence must contain RecommendationEvidence objects.'
                );
            }

            $normalized[$item->identity()] = $item;
        }

        return array_values($normalized);
    }

    /**
     * Merge the legacy primary action with additional actions.
     *
     * @param RecommendationAction|null $legacyaction
     * @param array $actions
     * @return RecommendationAction[]
     */
    private function normalize_actions(
        ?RecommendationAction $legacyaction,
        array $actions
    ): array {
        $normalized = [];

        if ($legacyaction !== null) {
            $normalized[$legacyaction->identity()] = $legacyaction;
        }

        foreach ($actions as $item) {
            if (!$item instanceof RecommendationAction) {
                throw new \InvalidArgumentException(
                    'Recommendation actions must contain RecommendationAction objects.'
                );
            }

            $normalized[$item->identity()] = $item;
        }

        return array_values($normalized);
    }

    /**
     * Validate and derive recommendation sources.
     *
     * Evidence sources are automatically added, which prevents a generator
     * from declaring evidence without declaring its contributing subsystem.
     *
     * @param array $sources
     * @param RecommendationEvidence[] $evidence
     * @return string[]
     */
    private function normalize_sources(array $sources, array $evidence): array {
        $normalized = [];

        foreach ($sources as $source) {
            if (
                !is_string($source) ||
                !RecommendationSource::is_valid($source)
            ) {
                throw new \InvalidArgumentException(
                    'Invalid recommendation source.'
                );
            }

            $normalized[$source] = $source;
        }

        foreach ($evidence as $item) {
            $normalized[$item->source] = $item->source;
        }

        return array_values($normalized);
    }

    /**
     * Validate a stable recommendation key.
     */
    private function is_valid_key(string $key): bool {
        return preg_match('/^[a-z][a-z0-9_.-]{1,99}$/', $key) === 1;
    }
}