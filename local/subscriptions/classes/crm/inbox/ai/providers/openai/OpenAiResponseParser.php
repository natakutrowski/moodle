<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\openai\dto\OpenAiResponsesResult;
use local_subscriptions\crm\inbox\ai\providers\openai\exceptions\OpenAiResponseException;

final class OpenAiResponseParser {

    public function structured_data(
        OpenAiResponsesResult $response
    ): array {
        $texts = [];
        $refusals = [];

        foreach ($response->output as $item) {
            if (
                !is_array($item) ||
                ($item['type'] ?? '') !==
                    'message'
            ) {
                continue;
            }

            foreach (
                $item['content'] ?? []
                as $content
            ) {
                if (!is_array($content)) {
                    continue;
                }

                if (
                    ($content['type'] ?? '') ===
                    'output_text'
                ) {
                    $texts[] =
                        (string)($content['text'] ?? '');
                }

                if (
                    ($content['type'] ?? '') ===
                    'refusal'
                ) {
                    $refusals[] =
                        (string)(
                            $content['refusal']
                            ?? ''
                        );
                }
            }
        }

        if ($refusals) {
            throw new OpenAiResponseException(
                'OpenAI refused the request: ' .
                implode(' ', $refusals)
            );
        }

        $text = trim(
            implode(PHP_EOL, $texts)
        );

        if ($text === '') {
            throw new OpenAiResponseException(
                'OpenAI returned no structured output.'
            );
        }

        try {
            $decoded = json_decode(
                $text,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $exception) {
            throw new OpenAiResponseException(
                'OpenAI structured output is invalid JSON.',
                0,
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new OpenAiResponseException(
                'OpenAI structured output is not an object.'
            );
        }

        return $decoded;
    }
}