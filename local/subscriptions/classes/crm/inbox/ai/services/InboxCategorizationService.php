<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxCategoryResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxCategoryPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxCategorizationService {

    private const CACHE_TTL = DAYSECS * 7;

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiContentSanitizer $sanitizer,
        private readonly InboxCategoryPromptBuilder $prompt
    ) {
    }

    public function categorize(
        int $threadid,
        ?int $messageid,
        string $content,
        array $context = [],
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxCategoryResult {
        $decision = $this->safety->evaluate(
            InboxAiCapability::CATEGORIZATION,
            $context
        );

        if (!$decision->allowed) {
            return new InboxCategoryResult(
                'other',
                0.0,
                [],
                [],
                'none',
                false,
                $decision->warnings
            );
        }

        $context['promptversion'] =
            InboxCategoryPromptBuilder::VERSION;

        $context['ttlseconds'] =
            self::CACHE_TTL;

        $result = $this->orchestrator->analyse(
            new InboxAiRequest(
                InboxAiCapability::CATEGORIZATION,
                $threadid,
                $messageid,
                $this->sanitizer->sanitize($content),
                'auto',
                $context,
                $this->prompt->constraints(),
                $actorid
            ),
            $forcerefresh
        );

        return InboxCategoryResult::from_ai_result(
            $result
        );
    }
}