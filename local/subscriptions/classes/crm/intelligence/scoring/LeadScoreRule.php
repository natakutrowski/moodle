<?php

namespace local_subscriptions\crm\intelligence\scoring;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;

interface LeadScoreRule {

    public function evaluate(CrmIntelligenceSnapshot $snapshot): LeadScoreRuleResult;
}