<?php

namespace local_subscriptions\crm\intelligence\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\UserIntelligence;
use local_subscriptions\crm\intelligence\recommendations\RecommendationEngineResult;

/**
 * Immutable result of one complete CRM user computation.
 */
final class CrmUserComputationResult {

    public function __construct(
        public readonly CrmComputationContext $context,
        public readonly int $userid,
        public readonly UserIntelligence $intelligence,
        public readonly RecommendationEngineResult
            $recommendationresult,
        public readonly int $durationms
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'CRM computation user ID must be greater than zero.'
            );
        }

        if ($this->durationms < 0) {
            throw new \InvalidArgumentException(
                'CRM computation duration cannot be negative.'
            );
        }
    }

    /**
     * Number of final generated recommendations.
     *
     * @return int
     */
    public function recommendation_count(): int {
        return $this->recommendationresult->count();
    }

    /**
     * Serialize non-sensitive computation metadata.
     *
     * @return array
     */
    public function metadata(): array {
        return [
            'runid' => $this->context->runid,
            'source' => $this->context->source,
            'engineversion' =>
                $this->context->engineversion,
            'startedat' => $this->context->startedat,
            'userid' => $this->userid,
            'durationms' => $this->durationms,
            'recommendationcount' =>
                $this->recommendation_count(),
        ];
    }
}