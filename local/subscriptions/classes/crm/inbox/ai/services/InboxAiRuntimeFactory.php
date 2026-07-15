<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\context\InboxAiContextRegistry;
use local_subscriptions\crm\inbox\ai\context\InboxContactContextProvider;
use local_subscriptions\crm\inbox\ai\context\InboxThreadContextProvider;
use local_subscriptions\crm\inbox\ai\prompts\InboxCategoryPromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxLanguagePromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxReplyPromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxSummaryPromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxTonePromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxTranslationPromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxUrgencyPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;

final class InboxAiRuntimeFactory {

    private InboxAiOrchestrator $orchestrator;
    private InboxReadRepository $read;
    private InboxAiContentSanitizer $sanitizer;
    private InboxAiSafetyPolicy $safety;
    private InboxAiThreadContentBuilder $threadcontent;
    private InboxAiCrmContextBuilder $crmcontext;

    public function __construct(
        ?InboxAiOrchestrator $orchestrator = null
    ) {
        $this->orchestrator =
            $orchestrator ??
            \core\di::get(
                InboxAiOrchestrator::class
            );

        $this->read =
            new InboxReadRepository();

        $this->sanitizer =
            new InboxAiContentSanitizer();

        $this->safety =
            new InboxAiSafetyPolicy();

        $this->threadcontent =
            new InboxAiThreadContentBuilder(
                $this->read,
                $this->sanitizer
            );

        $registry =
            new InboxAiContextRegistry();

        $registry->register(
            new InboxThreadContextProvider(
                $this->read
            )
        );

        $registry->register(
            new InboxContactContextProvider(
                $this->read
            )
        );

        $this->crmcontext =
            new InboxAiCrmContextBuilder(
                $registry
            );
    }

    public function read_repository():
        InboxReadRepository {
        return $this->read;
    }

    public function language():
        InboxLanguageDetectionService {
        return new InboxLanguageDetectionService(
            $this->orchestrator,
            $this->safety,
            $this->sanitizer,
            new InboxLanguagePromptBuilder()
        );
    }

    public function urgency():
        InboxUrgencyService {
        return new InboxUrgencyService(
            $this->orchestrator,
            $this->safety,
            $this->sanitizer,
            new InboxUrgencyPromptBuilder()
        );
    }

    public function categorization():
        InboxCategorizationService {
        return new InboxCategorizationService(
            $this->orchestrator,
            $this->safety,
            $this->sanitizer,
            new InboxCategoryPromptBuilder()
        );
    }

    public function summary():
        InboxSummaryService {
        return new InboxSummaryService(
            $this->orchestrator,
            $this->safety,
            $this->threadcontent,
            new InboxSummaryPromptBuilder()
        );
    }

    public function reply():
        InboxReplySuggestionService {
        return new InboxReplySuggestionService(
            $this->orchestrator,
            $this->safety,
            $this->threadcontent,
            $this->crmcontext,
            new InboxReplyPromptBuilder()
        );
    }

    public function translation():
        InboxTranslationService {
        return new InboxTranslationService(
            $this->orchestrator,
            $this->safety,
            $this->sanitizer,
            new InboxTranslationPromptBuilder()
        );
    }

    public function tone():
        InboxToneAdaptationService {
        return new InboxToneAdaptationService(
            $this->orchestrator,
            $this->safety,
            $this->sanitizer,
            new InboxTonePromptBuilder()
        );
    }
}