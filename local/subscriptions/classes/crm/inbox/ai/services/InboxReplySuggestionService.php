<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxSuggestedReply;
use local_subscriptions\crm\inbox\ai\prompts\InboxReplyPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;

final class InboxReplySuggestionService {

    private const CACHE_TTL = HOURSECS;

    private const ALLOWED_TONES = [
        'professional',
        'friendly',
        'empathetic',
        'concise',
    ];

    public function __construct(
        private readonly InboxAiOrchestrator $orchestrator,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiThreadContentBuilder $contentbuilder,
        private readonly InboxAiCrmContextBuilder $contextbuilder,
        private readonly InboxReplyPromptBuilder $prompt
    ) {
    }

    public function suggest(
        int $threadid,
        string $language,
        string $tone = 'professional',
        ?int $actorid = null,
        bool $forcerefresh = false
    ): InboxSuggestedReply {
        $tone = $this->normalize_tone($tone);

        $decision = $this->safety->evaluate(
            InboxAiCapability::REPLY_SUGGESTION
        );

        if (!$decision->allowed) {
            return new InboxSuggestedReply(
                '',
                '',
                $language,
                $tone,
                0.0,
                array_merge(
                    $decision->warnings,
                    [
                        $decision->reason
                            ?? 'AI reply suggestion is blocked.',
                    ]
                ),
                true
            );
        }

        $conversation =
            $this->contentbuilder->build(
                $threadid
            );

        if ($conversation === '') {
            return new InboxSuggestedReply(
                '',
                '',
                $language,
                $tone,
                0.0,
                [
                    get_string(
                        'crm_inbox_ai_empty_conversation',
                        'local_subscriptions'
                    ),
                ],
                true
            );
        }

        $crmcontext =
            $this->contextbuilder->build(
                $threadid
            );

        $requestcontext = [
            'promptversion' =>
                InboxReplyPromptBuilder::VERSION,
            'ttlseconds' =>
                self::CACHE_TTL,
            'crmcontext' =>
                $this->provider_context(
                    $crmcontext->to_array()
                ),
        ];

        $result = $this->orchestrator->analyse(
            new InboxAiRequest(
                InboxAiCapability::REPLY_SUGGESTION,
                $threadid,
                null,
                $conversation,
                $language,
                $requestcontext,
                $this->prompt->constraints(
                    $language,
                    $tone
                ),
                $actorid
            ),
            $forcerefresh
        );

        $warnings = $result->warnings;

        $datawarnings =
            $result->data['warnings']
            ?? [];

        if (is_array($datawarnings)) {
            $warnings = array_merge(
                $warnings,
                array_map(
                    'strval',
                    $datawarnings
                )
            );
        }

        $warnings[] = get_string(
            'crm_inbox_ai_reply_requires_review',
            'local_subscriptions'
        );

        return new InboxSuggestedReply(
            trim(
                (string)(
                    $result->data['subject']
                    ?? ''
                )
            ),
            trim(
                (string)(
                    $result->data['body']
                    ?? ''
                )
            ),
            trim(
                (string)(
                    $result->data['language']
                    ?? $language
                )
            ),
            trim(
                (string)(
                    $result->data['tone']
                    ?? $tone
                )
            ),
            max(
                0.0,
                min(
                    1.0,
                    $result->confidence
                )
            ),
            array_values(
                array_unique(
                    array_filter($warnings)
                )
            ),
            true
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

    private function provider_context(
        array $context
    ): array {
        /*
         * Les identifiants Moodle restent internes.
         */
        if (
            isset(
                $context['sections']['contact']
                ['matcheduserid']
            )
        ) {
            unset(
                $context['sections']['contact']
                ['matcheduserid']
            );
        }

        unset($context['threadid']);

        return $context;
    }
}