<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

final class InboxLanguagePromptBuilder {

    public const VERSION = 'language-v1';

    public function constraints(): array {
        return [
            'outputformat' => 'json',
            'allowedlanguages' => [
                'fr',
                'en',
                'ru',
                'uk',
                'de',
                'es',
                'it',
                'pt',
                'unknown',
            ],
            'schema' => [
                'language' => 'string',
                'confidence' => 'number',
            ],
            'instructions' => [
                'Detect the main language of the customer message.',
                'Return an ISO 639-1 language code.',
                'Return unknown when the language cannot be determined.',
                'Do not translate or answer the message.',
            ],
        ];
    }
}