<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\contracts\InboxAiProviderInterface;
use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxAiResult;
use local_subscriptions\crm\inbox\ai\providers\openai\contracts\OpenAiResponsesClientInterface;

final class OpenAiInboxAiProvider
    implements InboxAiProviderInterface {

    public function __construct(
        private readonly OpenAiInboxConfiguration $configuration,
        private readonly OpenAiResponsesClientInterface $client,
        private readonly OpenAiInstructionBuilder $instructions,
        private readonly OpenAiSchemaRegistry $schemas,
        private readonly OpenAiResponseParser $parser
    ) {
    }

    public function key(): string {
        return 'openai';
    }

    public function is_available(): bool {
        return $this->configuration->available();
    }

    public function supports(
        string $capability
    ): bool {
        return InboxAiCapability::is_valid(
            $capability
        );
    }

    public function analyse(
        InboxAiRequest $request
    ): InboxAiResult {
        if (!$this->is_available()) {
            return InboxAiResult::unavailable(
                $request->capability,
                $this->key(),
                'OpenAI is not configured.'
            );
        }

        $schema =
            $this->schemas->schema(
                $request->capability
            );

        $payload = [
            'model' =>
                $this->configuration->model(),
            'instructions' =>
                $this->instructions->build(
                    $request
                ),
            'input' =>
                $this->instructions->input(
                    $request
                ),
            'max_output_tokens' =>
                $this->configuration
                    ->max_output_tokens(),
            'store' =>
                $this->configuration->store(),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' =>
                        $this->schemas->name(
                            $request->capability
                        ),
                    'schema' => $schema,
                    'strict' => true,
                ],
            ],
        ];

        $response = $this->client->create(
            $payload
        );

        $data =
            $this->parser
                ->structured_data(
                    $response
                );

        $confidence = max(
            0.0,
            min(
                1.0,
                (float)($data['confidence'] ?? 0)
            )
        );

        return new InboxAiResult(
            InboxAiStatus::SUCCESS,
            $request->capability,
            $this->key(),
            $response->model,
            $data,
            $confidence,
            [],
            null,
            time(),
            [
                'responseid' =>
                    $response->responseid,
                'requestid' =>
                    $response->requestid,
                'inputtokens' =>
                    $response->inputtokens,
                'outputtokens' =>
                    $response->outputtokens,
                'totaltokens' =>
                    $response->totaltokens,
                'incompletereason' =>
                    $response->incompletereason,
                'structuredoutput' => true,
                'storedremotely' =>
                    $this->configuration->store(),
            ]
        );
    }
}