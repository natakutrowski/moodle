<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

final class InboxSummaryPromptBuilder {

    public const VERSION = 'summary-v2';

    public function constraints(
        string $outputlanguage
    ): array {
        $language = self::language_name(
            $outputlanguage
        );

        return [
            'outputformat' => 'json',
            'outputlanguage' => $outputlanguage,
            'schema' => [
                'summary' => 'string',
                'keypoints' => 'array',
                'pendingquestions' => 'array',
                'customerrequests' => 'array',
                'language' => 'string',
                'confidence' => 'number',
            ],
            'instructions' => [
                'Summarize the full support conversation in ' . $language . '.',
                'The summary, keypoints, pendingquestions and customerrequests MUST all be written in ' . $language . '.',
                'Set the language field exactly to "' . $outputlanguage . '".',
                'Distinguish customer statements from support replies.',
                'Do not invent payment status, access status or account data.',
                'Do not recommend an automatic administrative action.',
                'Mention unresolved questions explicitly.',
                'Keep the summary concise and factual.',
                'Return no more than five key points.',
                'Return no more than five customer requests.',
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