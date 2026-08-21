<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

final class InboxTranslationPromptBuilder {

    public const VERSION = 'translation-v2';

    public function constraints(
        string $targetlanguage
    ): array {
        $language = self::language_name(
            $targetlanguage
        );

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
                'Translate the text faithfully into ' . $language . '.',
                'The translatedtext field MUST be written in ' . $language . '.',
                'Set targetlanguage exactly to "' . $targetlanguage . '".',
                'Preserve names, dates, prices, currencies, URLs and identifiers.',
                'Do not add explanations.',
                'Do not answer the message.',
                'Do not alter the meaning.',
                'Preserve paragraph structure.',
            ],
        ];
    }

    private static function language_name(
        string $language
    ): string {
        return match ($language) {
            'fr' => 'French',
            'ru' => 'Russian',
            'en' => 'English',
            default => 'the requested language',
        };
    }

}