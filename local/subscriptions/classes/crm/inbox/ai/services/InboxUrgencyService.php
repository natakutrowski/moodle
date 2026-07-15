<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxUrgencyResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxUrgencyPromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxLanguagePromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxUrgencyService {

    private const CACHE_TTL = DAYSECS * 7;

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiContentSanitizer $sanitizer,
        private readonly InboxUrgencyPromptBuilder $prompt
    ) {
    }

    public function classify(
        int $threadid,
        ?int $messageid,
        string $content,
        array $context = [],
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxUrgencyResult {
        $decision = $this->safety->evaluate(
            InboxAiCapability::URGENCY_CLASSIFICATION,
            $context
        );

        if (!$decision->allowed) {
            return new InboxUrgencyResult(
                'normal',
                0.0,
                [],
                'none',
                false,
                $decision->warnings
            );
        }

        $context['promptversion'] =
            InboxUrgencyPromptBuilder::VERSION;

        $context['ttlseconds'] =
            self::CACHE_TTL;

        $result = $this->orchestrator->analyse(
            new InboxAiRequest(
                InboxAiCapability::URGENCY_CLASSIFICATION,
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

        return InboxUrgencyResult::from_ai_result(
            $result
        );
    }
}