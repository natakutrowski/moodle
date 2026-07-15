<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxToneResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxTonePromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxToneAdaptationService {

    private const CACHE_TTL =
        HOURSECS * 6;

    private const ALLOWED_TONES = [
        'professional',
        'friendly',
        'empathetic',
        'concise',
    ];

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiContentSanitizer $sanitizer,
        private readonly InboxTonePromptBuilder $prompt
    ) {
    }

    public function adapt(
        int $threadid,
        string $content,
        string $tone,
        string $language,
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxToneResult {
        $tone = $this->normalize_tone(
            $tone
        );

        $decision = $this->safety->evaluate(
            InboxAiCapability::REPLY_SUGGESTION
        );

        if (!$decision->allowed) {
            return new InboxToneResult(
                '',
                $tone,
                $language,
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
            return new InboxToneResult(
                '',
                $tone,
                $language,
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
                InboxAiCapability::REPLY_SUGGESTION,
                $threadid,
                null,
                $content,
                $language,
                [
                    'promptversion' =>
                        InboxTonePromptBuilder::VERSION,
                    'ttlseconds' =>
                        self::CACHE_TTL,
                    'operation' =>
                        'tone_adaptation',
                ],
                $this->prompt->constraints(
                    $tone,
                    $language
                ),
                $actorid
            ),
            $forcerefresh
        );

        return InboxToneResult::from_ai_result(
            $result,
            $tone,
            $language
        );
    }

    private function normalize_tone(
        string $tone
    ): string {
        $tone = \core_text::strtolower(
            trim($tone)
        );

        return in_array(
            $tone,
            self::ALLOWED_TONES,
            true
        )
            ? $tone
            : 'professional';
    }
}