<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

final class InboxTranslationPromptBuilder {

    public const VERSION = 'translation-v1';

    public function constraints(
        string $targetlanguage
    ): array {
        return [
            'outputformat' => 'json',
            'targetlanguage' =>
                $targetlanguage,
            'schema' => [
                'translatedtext' => 'string',
                'sourcelanguage' => 'string',
                'targetlanguage' => 'string',
                'confidence' => 'number',
            ],
            'instructions' => [
                'Translate the text faithfully.',
                'Preserve names, dates, prices, currencies, URLs and identifiers.',
                'Do not add explanations.',
                'Do not answer the message.',
                'Do not alter the meaning.',
                'Preserve paragraph structure.',
            ],
        ];
    }
}