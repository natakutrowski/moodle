<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxSummaryResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxSummaryPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxSummaryService {

    private const CACHE_TTL = DAYSECS;

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiThreadContentBuilder $contentbuilder,
        private readonly InboxSummaryPromptBuilder $prompt
    ) {
    }

    public function summarize(
        int $threadid,
        string $outputlanguage,
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxSummaryResult {
        $decision = $this->safety->evaluate(
            InboxAiCapability::SUMMARY
        );

        if (!$decision->allowed) {
            return new InboxSummaryResult(
                '',
                [],
                [],
                [],
                $outputlanguage,
                0.0,
                'none',
                false,
                $decision->warnings
            );
        }

        $content = $this->contentbuilder->build(
            $threadid
        );

        if ($content === '') {
            return new InboxSummaryResult(
                '',
                [],
                [],
                [],
                $outputlanguage,
                0.0,
                'none',
                false,
                [
                    get_string(
                        'crm_inbox_ai_empty_conversation',
                        'local_subscriptions'
                    ),
                ]
            );
        }

        $result = $this->orchestrator->analyse(
            new InboxAiRequest(
                InboxAiCapability::SUMMARY,
                $threadid,
                null,
                $content,
                $outputlanguage,
                [
                    'promptversion' =>
                        InboxSummaryPromptBuilder::VERSION,
                    'ttlseconds' => self::CACHE_TTL,
                ],
                $this->prompt->constraints(
                    $outputlanguage
                ),
                $actorid
            ),
            $forcerefresh
        );

        return InboxSummaryResult::from_ai_result(
            $result
        );
    }
}