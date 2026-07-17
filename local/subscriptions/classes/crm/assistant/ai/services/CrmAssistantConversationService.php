<?php

namespace local_subscriptions\crm\assistant\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantResult;
use local_subscriptions\crm\assistant\ai\safety\CrmAssistantSafetyPolicy;

/**
 * Application service for conversational CRM Assistant requests.
 */
final class CrmAssistantConversationService {

    public function __construct(
        private readonly CrmAssistantContextBuilder $contexts =
            new CrmAssistantContextBuilder(),
        private readonly CrmAssistantSafetyPolicy $safety =
            new CrmAssistantSafetyPolicy(),
        private readonly CrmAssistantAiRuntimeFactory $runtime =
            new CrmAssistantAiRuntimeFactory()
    ) {
    }

    public function ask(
        CrmAssistantQuestion $question
    ): CrmAssistantResult {
        try {
            $this->safety->validate(
                $question
            );

            $sanitized =
                $this->safety
                    ->sanitize_question(
                        $question->question
                    );

            $safequestion =
                new CrmAssistantQuestion(
                    question: $sanitized,
                    language:
                        $question->language,
                    scope: $question->scope,
                    userid:
                        $question->userid,
                    recommendationid:
                        $question->recommendationid
                );

            $context =
                $this->contexts->build(
                    $safequestion
                );

            return $this->runtime
                ->provider()
                ->answer(
                    $safequestion,
                    $context
                );
        } catch (\domain_exception) {
            return CrmAssistantResult::rejected(
                'question_rejected'
            );
        } catch (\Throwable $exception) {
            debugging(
                'CRM Assistant conversation failure: ' .
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );

            return CrmAssistantResult::failed(
                'conversation_failed',
                [
                    'exceptionclass' =>
                        get_class($exception),
                ]
            );
        }
    }
}