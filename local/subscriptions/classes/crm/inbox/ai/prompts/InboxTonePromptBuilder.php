<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

final class InboxTonePromptBuilder {

    public const VERSION = 'tone-v1';

    public function constraints(
        string $tone,
        string $language
    ): array {
        return [
            'outputformat' => 'json',
            'tone' => $tone,
            'language' => $language,
            'schema' => [
                'text' => 'string',
                'tone' => 'string',
                'language' => 'string',
                'confidence' => 'number',
            ],
            'instructions' => [
                'Rewrite the supplied draft without changing its factual meaning.',
                'Do not add promises, refunds, access confirmations or payment confirmations.',
                'Preserve URLs, dates, amounts, product names and names.',
                'Do not remove warnings or conditions.',
                'Do not send the reply.',
            ],
        ];
    }
}