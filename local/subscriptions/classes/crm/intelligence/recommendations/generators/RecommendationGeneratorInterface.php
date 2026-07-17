<?php

namespace local_subscriptions\crm\intelligence\recommendations\generators;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationResult;

/**
 * Contract implemented by every recommendation generator.
 */
interface RecommendationGeneratorInterface {

    /**
     * Stable technical generator identifier.
     */
    public function key(): string;

    /**
     * Generate recommendations from a preloaded intelligence context.
     *
     * Generators must:
     * - remain deterministic for an equivalent context;
     * - execute no presentation logic;
     * - execute no recommendation action;
     * - create no Work Item automatically;
     * - perform no direct SQL.
     */
    public function generate(
        RecommendationGenerationContext $context
    ): RecommendationGenerationResult;
}