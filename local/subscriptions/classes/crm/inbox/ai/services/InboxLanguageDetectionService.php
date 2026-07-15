<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxLanguageResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxLanguagePromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxLanguageDetectionService {

    private const CACHE_TTL = DAYSECS * 30;

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiContentSanitizer $sanitizer,
        private readonly InboxLanguagePromptBuilder $prompt
    ) {
    }

    public function detect(
        int $threadid,
        ?int $messageid,
        string $content,
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxLanguageResult {
        $decision = $this->safety->evaluate(
            InboxAiCapability::LANGUAGE_DETECTION
        );

        if (!$decision->allowed) {
            return new InboxLanguageResult(
                'unknown',
                0.0,
                'none',
                false,
                $decision->warnings
            );
        }

        $content = $this->sanitizer->sanitize(
            $content
        );

        if ($content === '') {
            return new InboxLanguageResult(
                'unknown',
                0.0,
                'none',
                false,
                [
                    get_string(
                        'crm_inbox_ai_empty_content',
                        'local_subscriptions'
                    ),
                ]
            );
        }

        $result = $this->orchestrator->analyse(
            new InboxAiRequest(
                InboxAiCapability::LANGUAGE_DETECTION,
                $threadid,
                $messageid,
                $content,
                'auto',
                [
                    'promptversion' =>
                        InboxLanguagePromptBuilder::VERSION,
                    'ttlseconds' => self::CACHE_TTL,
                ],
                $this->prompt->constraints(),
                $actorid
            ),
            $forcerefresh
        );

        return InboxLanguageResult::from_ai_result(
            $result
        );
    }
}