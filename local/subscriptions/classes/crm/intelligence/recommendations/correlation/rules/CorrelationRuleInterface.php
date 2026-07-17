<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationMatch;

/**
 * Contract implemented by deterministic correlation scenarios.
 */
interface CorrelationRuleInterface {

    /**
     * Stable technical rule identifier.
     */
    public function key(): string;

    /**
     * Analyze one user context.
     *
     * Return null when the scenario is not present.
     */
    public function match(
        CorrelationContext $context
    ): ?CorrelationMatch;
}