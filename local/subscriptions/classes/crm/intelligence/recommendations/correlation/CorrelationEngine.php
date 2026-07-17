<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationDeduplicator;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\ChurnRiskCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\CorrelationRuleInterface;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\DisengagementSpiralCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\LearningSupportPressureCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\OperationalOverloadCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\PaymentSupportFrictionCorrelationRule;

/**
 * Coordinates deterministic cross-domain recommendation rules.
 */
final class CorrelationEngine {

    /**
     * @var CorrelationRuleInterface[]
     */
    private readonly array $rules;

    private readonly RecommendationDeduplicator
        $deduplicator;

    /**
     * @param CorrelationRuleInterface[]|null $rules
     */
    public function __construct(
        ?array $rules = null,
        ?RecommendationDeduplicator $deduplicator = null
    ) {
        $this->rules = $this->normalize_rules(
            $rules ?? $this->default_rules()
        );

        $this->deduplicator =
            $deduplicator ??
            new RecommendationDeduplicator();
    }

    /**
     * @param Recommendation[] $recommendations
     */
    public function correlate(
        RecommendationGenerationContext $generationcontext,
        array $recommendations
    ): CorrelationEngineResult {
        $context = new CorrelationContext(
            $generationcontext,
            $recommendations
        );

        /*
         * Correlation requires Customer Success signals.
         * Existing recommendations remain untouched if unavailable.
         */
        if (!$context->has_customer_success()) {
            return new CorrelationEngineResult(
                recommendations:
                    $recommendations,
                matches: [],
                suppressedrecommendationkeys: [],
                ruleerrors: []
            );
        }

        $matches = [];
        $errors = [];

        foreach ($this->rules as $rule) {
            try {
                $match = $rule->match($context);

                if ($match !== null) {
                    $matches[] = $match;
                }
            } catch (\Throwable $exception) {
                debugging(
                    sprintf(
                        'Correlation rule "%s" failed: %s',
                        $rule->key(),
                        $exception->getMessage()
                    ),
                    DEBUG_DEVELOPER
                );

                $errors[$rule->key()] =
                    get_class($exception);
            }
        }

        $matches =
            $this->resolve_competing_matches(
                $matches
            );

        $suppressedkeys =
            $this->collect_suppressed_keys(
                $matches
            );

        $finalrecommendations =
            $this->remove_suppressed(
                $recommendations,
                $suppressedkeys
            );

        foreach ($matches as $match) {
            $finalrecommendations[] =
                $match->recommendation;
        }

        $finalrecommendations =
            $this->deduplicator->deduplicate(
                $finalrecommendations
            );

        $this->sort_recommendations(
            $finalrecommendations
        );

        return new CorrelationEngineResult(
            recommendations:
                $finalrecommendations,
            matches: $matches,
            suppressedrecommendationkeys:
                $suppressedkeys,
            ruleerrors: $errors
        );
    }

    /**
     * @return string[]
     */
    public function rule_keys(): array {
        return array_values(array_map(
            static fn(
                CorrelationRuleInterface $rule
            ): string => $rule->key(),
            $this->rules
        ));
    }

    /**
     * Return all built-in cross-domain scenarios.
     *
     * More specific rules run before broader scenarios.
     *
     * @return CorrelationRuleInterface[]
     */
    private function default_rules(): array {
        return [
            new ChurnRiskCorrelationRule(),
            new PaymentSupportFrictionCorrelationRule(),
            new LearningSupportPressureCorrelationRule(),
            new OperationalOverloadCorrelationRule(),
            new DisengagementSpiralCorrelationRule(),
        ];
    }

    /**
     * Resolve matches that suppress one another.
     *
     * A high-confidence churn scenario takes precedence over a broader
     * disengagement scenario when both refer to the same user.
     *
     * @param CorrelationMatch[] $matches
     * @return CorrelationMatch[]
     */
    private function resolve_competing_matches(
        array $matches
    ): array {
        usort(
            $matches,
            static function (
                CorrelationMatch $left,
                CorrelationMatch $right
            ): int {
                $confidencecomparison =
                    $right->confidencescore <=>
                    $left->confidencescore;

                if ($confidencecomparison !== 0) {
                    return $confidencecomparison;
                }

                $prioritycomparison =
                    $right->recommendation->priority <=>
                    $left->recommendation->priority;

                if ($prioritycomparison !== 0) {
                    return $prioritycomparison;
                }

                return strcmp(
                    $left->rulekey,
                    $right->rulekey
                );
            }
        );

        $selected = [];
        $selectedrecommendationkeys = [];

        foreach ($matches as $match) {
            $key = $match->recommendation->key;

            if (
                isset(
                    $selectedrecommendationkeys[$key]
                )
            ) {
                continue;
            }

            /*
             * The churn-risk scenario already includes disengagement.
             */
            if (
                $match->rulekey ===
                    DisengagementSpiralCorrelationRule::KEY &&
                $this->contains_rule(
                    $selected,
                    ChurnRiskCorrelationRule::KEY
                )
            ) {
                continue;
            }

            $selectedrecommendationkeys[$key] =
                true;
            $selected[] = $match;
        }

        return $selected;
    }

    /**
     * @param CorrelationMatch[] $matches
     */
    private function contains_rule(
        array $matches,
        string $rulekey
    ): bool {
        foreach ($matches as $match) {
            if ($match->rulekey === $rulekey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CorrelationMatch[] $matches
     * @return string[]
     */
    private function collect_suppressed_keys(
        array $matches
    ): array {
        $keys = [];

        foreach ($matches as $match) {
            if (
                !$match
                    ->should_suppress_components()
            ) {
                continue;
            }

            foreach (
                $match
                    ->suppressedrecommendationkeys
                as $key
            ) {
                $keys[$key] = $key;
            }
        }

        return array_values($keys);
    }

    /**
     * @param Recommendation[] $recommendations
     * @param string[] $suppressedkeys
     * @return Recommendation[]
     */
    private function remove_suppressed(
        array $recommendations,
        array $suppressedkeys
    ): array {
        if ($suppressedkeys === []) {
            return array_values(
                $recommendations
            );
        }

        return array_values(array_filter(
            $recommendations,
            static fn(
                Recommendation $recommendation
            ): bool => !in_array(
                $recommendation->key,
                $suppressedkeys,
                true
            )
        ));
    }

    /**
     * @param CorrelationRuleInterface[] $rules
     * @return CorrelationRuleInterface[]
     */
    private function normalize_rules(
        array $rules
    ): array {
        $normalized = [];
        $keys = [];

        foreach ($rules as $rule) {
            if (
                !$rule instanceof
                CorrelationRuleInterface
            ) {
                throw new \InvalidArgumentException(
                    'Correlation rules must implement CorrelationRuleInterface.'
                );
            }

            $key = $rule->key();

            if (
                preg_match(
                    '/^[a-z][a-z0-9_.-]{1,99}$/',
                    $key
                ) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Invalid correlation rule key.'
                );
            }

            if (isset($keys[$key])) {
                throw new \InvalidArgumentException(
                    'Duplicate correlation rule key: ' .
                    $key
                );
            }

            $keys[$key] = true;
            $normalized[] = $rule;
        }

        return $normalized;
    }

    /**
     * @param Recommendation[] $recommendations
     */
    private function sort_recommendations(
        array &$recommendations
    ): void {
        usort(
            $recommendations,
            static function (
                Recommendation $left,
                Recommendation $right
            ): int {
                $prioritycomparison =
                    $right->priority <=>
                    $left->priority;

                if ($prioritycomparison !== 0) {
                    return $prioritycomparison;
                }

                return strcmp(
                    $left->key,
                    $right->key
                );
            }
        );
    }
}