<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxTranslationResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxTranslationPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxTranslationService {

    private const CACHE_TTL =
        DAYSECS * 30;

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiContentSanitizer $sanitizer,
        private readonly InboxTranslationPromptBuilder $prompt
    ) {
    }

    public function translate(
        int $threadid,
        ?int $messageid,
        string $content,
        string $targetlanguage,
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxTranslationResult {
        $decision = $this->safety->evaluate(
            InboxAiCapability::TRANSLATION
        );

        if (!$decision->allowed) {
            return new InboxTranslationResult(
                '',
                'unknown',
                $targetlanguage,
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
            return new InboxTranslationResult(
                '',
                'unknown',
                $targetlanguage,
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
                InboxAiCapability::TRANSLATION,
                $threadid,
                $messageid,
                $content,
                $targetlanguage,
                [
                    'promptversion' =>
                        InboxTranslationPromptBuilder::VERSION,
                    'ttlseconds' =>
                        self::CACHE_TTL,
                ],
                $this->prompt->constraints(
                    $targetlanguage
                ),
                $actorid
            ),
            $forcerefresh
        );

        return InboxTranslationResult::
            from_ai_result(
                $result,
                $targetlanguage
            );
    }
}