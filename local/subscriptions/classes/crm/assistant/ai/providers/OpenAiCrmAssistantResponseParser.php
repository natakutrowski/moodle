<?php

namespace local_subscriptions\crm\assistant\ai\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\openai\dto\OpenAiResponsesResult;

/**
 * Extracts structured JSON from an OpenAI Responses API result.
 */
final class OpenAiCrmAssistantResponseParser {

    public function parse(
        OpenAiResponsesResult $result
    ): array {
        foreach ($result->output as $outputitem) {
            if (
                !is_array($outputitem) ||
                !isset($outputitem['content']) ||
                !is_array($outputitem['content'])
            ) {
                continue;
            }

            foreach (
                $outputitem['content']
                as $content
            ) {
                if (!is_array($content)) {
                    continue;
                }

                $text =
                    $content['text'] ?? null;

                if (!is_string($text)) {
                    continue;
                }

                $decoded = json_decode(
                    $text,
                    true
                );

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        throw new \UnexpectedValueException(
            'OpenAI CRM Assistant response did not contain structured JSON.'
        );
    }
}