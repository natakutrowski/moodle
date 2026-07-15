<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\cache\InboxAiCacheKeyBuilder;
use local_subscriptions\crm\inbox\ai\cache\InboxAiCachePolicy;
use local_subscriptions\crm\inbox\ai\prompts\InboxAiPromptVersionRegistry;
use local_subscriptions\crm\inbox\ai\providers\InboxAiProviderRegistry;
use local_subscriptions\crm\inbox\ai\providers\fallback\FallbackInboxAiProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxAiProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInstructionBuilder;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponseParser;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponsesClient;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiSchemaRegistry;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiResultRepository;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiDataMinimizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;
use local_subscriptions\crm\inbox\ai\safety\InboxAiErrorSanitizer;
use local_subscriptions\crm\inbox\ai\validation\InboxAiResultValidator;

final class InboxAiServiceFactory {

    public static function orchestrator():
        InboxAiOrchestrator {

        $keys =
            new OpenAiApiKeyProvider();

        $configuration =
            new OpenAiInboxConfiguration(
                $keys
            );

        $openai =
            self::build_openai_provider(
                $configuration
            );

        $providers =
            new InboxAiProviderRegistry([
                $openai,
                new FallbackInboxAiProvider(),
            ]);

        return new InboxAiOrchestrator(
            $providers,
            new InboxAiResultRepository(),
            new InboxAiSafetyPolicy(),
            new InboxAiContentSanitizer(),
            new InboxAiPromptVersionRegistry(),
            new InboxAiCacheKeyBuilder(),
            new InboxAiCachePolicy(),

            /*
             * 8e argument :
             * validation locale des résultats.
             */
            new InboxAiResultValidator(),

            /*
             * 9e argument :
             * minimisation des données avant provider/cache.
             */
            new InboxAiDataMinimizer(
                $configuration
            ),

            new InboxAiErrorSanitizer()
        );
    }

    public static function openai_provider():
        OpenAiInboxAiProvider {

        $keys =
            new OpenAiApiKeyProvider();

        $configuration =
            new OpenAiInboxConfiguration(
                $keys
            );

        return self::build_openai_provider(
            $configuration
        );
    }

    private static function build_openai_provider(
        OpenAiInboxConfiguration $configuration
    ): OpenAiInboxAiProvider {
        return new OpenAiInboxAiProvider(
            $configuration,
            new OpenAiResponsesClient(
                $configuration
            ),
            new OpenAiInstructionBuilder(),
            new OpenAiSchemaRegistry(),
            new OpenAiResponseParser()
        );
    }
}