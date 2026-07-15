<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\openai\contracts\OpenAiResponsesClientInterface;
use local_subscriptions\crm\inbox\ai\providers\openai\dto\OpenAiResponsesResult;
use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiConfigurationException;
use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiResponseException;
use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiTransportException;

global $CFG;
require_once($CFG->libdir . '/filelib.php');

final class OpenAiResponsesClient
    implements OpenAiResponsesClientInterface {

    public function __construct(
        private readonly OpenAiInboxConfiguration $configuration
    ) {
    }

    public function create(
        array $payload
    ): OpenAiResponsesResult {
        
        $apikey =
            $this->configuration
                ->keys()
                ->get();

        if ($apikey === '') {
            throw new OpenAiConfigurationException(
                'OpenAI API key is missing.'
            );
        }

        $curl = new \curl();

        $headers = [
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $project =
            $this->configuration
                ->keys()
                ->project_id();

        if ($project !== '') {
            $headers[] =
                'OpenAI-Project: ' . $project;
        }

        $organization =
            $this->configuration
                ->keys()
                ->organization_id();

        if ($organization !== '') {
            $headers[] =
                'OpenAI-Organization: ' .
                $organization;
        }

        $clientrequestid =
            $this->client_request_id();

        $headers[] =
            'X-Client-Request-Id: ' .
            $clientrequestid;

        $curl->setHeader($headers);

        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        $raw = $curl->post(
            $this->configuration->endpoint(),
            $json,
            [
                'CURLOPT_TIMEOUT' =>
                    $this->configuration->timeout(),
                'CURLOPT_CONNECTTIMEOUT' => 10,
            ]
        );

        $info = $curl->get_info();

        if ($raw === false) {
            throw new OpenAiTransportException(
                'OpenAI request failed before a response was received.'
            );
        }

        $httpcode = (int)(
            $info['http_code'] ?? 0
        );

        try {
            $decoded = json_decode(
                (string)$raw,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $exception) {
            throw new OpenAiResponseException(
                'OpenAI returned invalid JSON.',
                0,
                $exception
            );
        }

        if ($httpcode < 200 || $httpcode >= 300) {
            $message = trim(
                (string)(
                    $decoded['error']['message']
                    ?? 'OpenAI request failed.'
                )
            );

            throw new OpenAiResponseException(
                sprintf(
                    'OpenAI HTTP %d: %s',
                    $httpcode,
                    $message
                )
            );
        }

        $requestid = $this->header_value(
            $info,
            'x-request-id'
        );

        return new OpenAiResponsesResult(
            (string)($decoded['id'] ?? ''),
            $requestid,
            (string)(
                $decoded['model']
                ?? $this->configuration->model()
            ),
            (string)($decoded['status'] ?? ''),
            is_array($decoded['output'] ?? null)
                ? $decoded['output']
                : [],
            (int)(
                $decoded['usage']['input_tokens']
                ?? 0
            ),
            (int)(
                $decoded['usage']['output_tokens']
                ?? 0
            ),
            (int)(
                $decoded['usage']['total_tokens']
                ?? 0
            ),
            isset(
                $decoded['incomplete_details']['reason']
            )
                ? (string)$decoded[
                    'incomplete_details'
                ]['reason']
                : null,
            [
                'clientrequestid' =>
                    $clientrequestid,
                'httpcode' => $httpcode,
            ]
        );
    }

    private function client_request_id(): string {
        return sprintf(
            'campusfr-inbox-%s-%s',
            time(),
            bin2hex(random_bytes(8))
        );
    }

    private function header_value(
        array $info,
        string $wanted
    ): ?string {
        /*
         * Moodle curl ne garantit pas toujours
         * l’exposition des headers dans get_info().
         * Le client request ID reste donc notre trace fiable.
         */
        return isset($info[$wanted])
            ? trim((string)$info[$wanted])
            : null;
    }
}