<?php

namespace local_subscriptions\crm\assistant\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\providers\OpenAiCrmAssistantProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponsesClient;

/**
 * Builds the conversational CRM Assistant runtime.
 */
final class CrmAssistantAiRuntimeFactory {

    public function provider():
        OpenAiCrmAssistantProvider {
        $keys =
            new OpenAiApiKeyProvider();

        $configuration =
            new OpenAiInboxConfiguration(
                $keys
            );

        $client =
            new OpenAiResponsesClient(
                $configuration
            );

        return new OpenAiCrmAssistantProvider(
            configuration:
                $configuration,
            client: $client
        );
    }
}