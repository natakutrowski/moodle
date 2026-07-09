<?php

namespace local_subscriptions\crm\intelligence\core;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\scoring\LeadScore;
use local_subscriptions\crm\intelligence\trends\CrmScoreTrend;

final class UserIntelligence {

    public function __construct(
        public readonly CrmIntelligenceSnapshot $snapshot,
        public readonly LeadScore $leadScore,
        public readonly array $segments = [],
        public readonly array $opportunities = [],
        public readonly array $recommendations = [],
        public readonly ?CrmScoreTrend $trend = null,
        public readonly array $explanations = []
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'leadscore' => $this->leadScore->to_object(),
            'segments' => array_map(static fn($segment): \stdClass => $segment->to_object(), $this->segments),
            'opportunities' => array_map(static fn($opportunity): \stdClass => $opportunity->to_object(), $this->opportunities),
            'recommendations' => array_map(static fn($recommendation): \stdClass => $recommendation->to_object(), $this->recommendations),
            'trend' => $this->trend?->to_object(),
            'explanations' => array_map(static fn($explanation): \stdClass => $explanation->to_object(), $this->explanations),
        ];
    }
}