<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;

/**
 * Complete result of one Correlation Engine run.
 */
final class CorrelationEngineResult {

    /**
     * @var Recommendation[]
     */
    public readonly array $recommendations;

    /**
     * @var CorrelationMatch[]
     */
    public readonly array $matches;

    /**
     * @var string[]
     */
    public readonly array $suppressedrecommendationkeys;

    /**
     * @param Recommendation[] $recommendations Final recommendations after suppression.
     * @param CorrelationMatch[] $matches Successful correlation matches.
     * @param string[] $suppressedrecommendationkeys Removed component recommendation keys.
     * @param array<string,string> $ruleerrors Sanitized rule errors.
     */
    public function __construct(
        array $recommendations,
        array $matches,
        array $suppressedrecommendationkeys,
        public readonly array $ruleerrors = []
    ) {
        $this->recommendations =
            $this->validate_recommendations(
                $recommendations
            );

        $this->matches =
            $this->validate_matches($matches);

        $this->suppressedrecommendationkeys =
            array_values(
                array_unique(
                    $suppressedrecommendationkeys
                )
            );

        foreach (
            $this->ruleerrors
            as $rulekey => $error
        ) {
            if (
                !is_string($rulekey) ||
                !is_string($error)
            ) {
                throw new \InvalidArgumentException(
                    'Invalid correlation rule error.'
                );
            }
        }
    }

    public function has_matches(): bool {
        return $this->matches !== [];
    }

    public function has_errors(): bool {
        return $this->ruleerrors !== [];
    }

    public function match_count(): int {
        return count($this->matches);
    }

    public function to_object(): \stdClass {
        return (object)[
            'matchcount' => $this->match_count(),
            'hasmatches' => $this->has_matches(),
            'haserrors' => $this->has_errors(),
            'suppressedrecommendationkeys' =>
                $this->suppressedrecommendationkeys,
            'matches' => array_map(
                static fn(
                    CorrelationMatch $match
                ): \stdClass => $match->to_object(),
                $this->matches
            ),
            'ruleerrors' => $this->ruleerrors,
        ];
    }

    /**
     * @return Recommendation[]
     */
    private function validate_recommendations(
        array $recommendations
    ): array {
        foreach ($recommendations as $recommendation) {
            if (
                !$recommendation instanceof Recommendation
            ) {
                throw new \InvalidArgumentException(
                    'Correlation result requires Recommendation objects.'
                );
            }
        }

        return array_values($recommendations);
    }

    /**
     * @return CorrelationMatch[]
     */
    private function validate_matches(
        array $matches
    ): array {
        foreach ($matches as $match) {
            if (!$match instanceof CorrelationMatch) {
                throw new \InvalidArgumentException(
                    'Correlation result requires CorrelationMatch objects.'
                );
            }
        }

        return array_values($matches);
    }
}