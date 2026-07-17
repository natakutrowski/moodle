<?php

namespace local_subscriptions\crm\assistant\ai\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\dto\CrmAssistantAnswer;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantContext;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantReference;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantResult;
use local_subscriptions\crm\assistant\ai\prompts\CrmAssistantPromptBuilder;
use local_subscriptions\crm\assistant\ai\prompts\CrmAssistantSchema;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponsesClient;

/**
 * OpenAI provider for the conversational CRM Assistant.
 *
 * Reuses the existing Responses API client and OpenAI configuration.
 */
final class OpenAiCrmAssistantProvider
    implements CrmAssistantProviderInterface {

    public function __construct(
        private readonly OpenAiInboxConfiguration $configuration,
        private readonly OpenAiResponsesClient $client,
        private readonly CrmAssistantPromptBuilder $prompts =
            new CrmAssistantPromptBuilder(),
        private readonly CrmAssistantSchema $schema =
            new CrmAssistantSchema(),
        private readonly OpenAiCrmAssistantResponseParser $parser =
            new OpenAiCrmAssistantResponseParser()
    ) {
    }

    public function available(): bool {
        return $this->configuration->available();
    }

    public function answer(
        CrmAssistantQuestion $question,
        CrmAssistantContext $context
    ): CrmAssistantResult {
        if (!$this->available()) {
            return CrmAssistantResult::unavailable(
                'openai_unavailable'
            );
        }

        try {
            $response = $this->client->create([
                'model' =>
                    $this->configuration->model(),
                'store' => false,
                'max_output_tokens' => min(
                    3000,
                    $this->configuration
                        ->max_output_tokens()
                ),
                'instructions' =>
                    $this->prompts
                        ->instructions($question),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' =>
                                    'input_text',
                                'text' =>
                                    $this->prompts->input(
                                        $question,
                                        $context
                                    ),
                            ],
                        ],
                    ],
                ],
                'text' => [
                    'format' => [
                        'type' =>
                            'json_schema',
                        'name' =>
                            $this->schema->name(),
                        'strict' => true,
                        'schema' =>
                            $this->schema->schema(),
                    ],
                ],
            ]);

            $data =
                $this->parser->parse(
                    $response
                );

            $references =
                $this->map_references(
                    $data['references'] ?? [],
                    $context->allowedreferences
                );

            $answer =
                new CrmAssistantAnswer(
                    answer:
                        trim(
                            (string)($data['answer'] ?? '')
                        ),
                    keypoints:
                        $this->strings(
                            $data['keypoints'] ?? []
                        ),
                    suggestedactions:
                        $this->strings(
                            $data['suggestedactions'] ?? []
                        ),
                    warnings:
                        $this->strings(
                            $data['warnings'] ?? []
                        ),
                    references: $references,
                    confidence:
                        (float)($data['confidence'] ?? 0),
                    requiresreview: true,
                    metadata: [
                        'provider' => 'openai',
                        'model' =>
                            $response->model,
                        'responseid' =>
                            $response->responseid,
                        'inputtokens' =>
                            $response->inputtokens,
                        'outputtokens' =>
                            $response->outputtokens,
                        'totaltokens' =>
                            $response->totaltokens,
                        'promptversion' =>
                            CrmAssistantPromptBuilder::VERSION,
                    ]
                );

            return CrmAssistantResult::success(
                $answer,
                $answer->metadata
            );
        } catch (\Throwable $exception) {
            debugging(
                'CRM Assistant OpenAI failure: ' .
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );

            return CrmAssistantResult::failed(
                'openai_request_failed',
                [
                    'exceptionclass' =>
                        get_class($exception),
                ]
            );
        }
    }

    /**
     * References not present in the supplied context are discarded.
     *
     * @return CrmAssistantReference[]
     */
    private function map_references(
        array $references,
        array $allowed
    ): array {
        $allowedindex = [];

        foreach ($allowed as $reference) {
            if (
                !isset(
                    $reference['type'],
                    $reference['id']
                )
            ) {
                continue;
            }

            $key =
                $reference['type'] .
                ':' .
                (int)$reference['id'];

            $allowedindex[$key] = true;
        }

        $mapped = [];

        foreach ($references as $reference) {
            if (!is_array($reference)) {
                continue;
            }

            $type =
                (string)($reference['type'] ?? '');

            $id =
                (int)($reference['id'] ?? 0);

            $key = $type . ':' . $id;

            if (
                $id <= 0 ||
                !isset($allowedindex[$key])
            ) {
                continue;
            }

            try {
                $mapped[] =
                    new CrmAssistantReference(
                        type: $type,
                        id: $id,
                        label:
                            trim(
                                (string)(
                                    $reference['label']
                                    ?? $key
                                )
                            ),
                        reason:
                            isset($reference['reason'])
                                ? trim(
                                    (string)$reference['reason']
                                )
                                : null
                    );
            } catch (\Throwable) {
                continue;
            }
        }

        return $mapped;
    }

    private function strings(
        mixed $values
    ): array {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn(mixed $value): string =>
                    trim((string)$value),
                $values
            ),
            static fn(string $value): bool =>
                $value !== ''
        ));
    }
}