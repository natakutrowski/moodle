<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

final class InboxReplyPromptBuilder {

    public const VERSION =
        'reply-suggestion-v1';

    public function constraints(
        string $language,
        string $tone
    ): array {
        return [
            'outputformat' => 'json',
            'outputlanguage' => $language,
            'tone' => $tone,
            'schema' => [
                'subject' => 'string',
                'body' => 'string',
                'language' => 'string',
                'tone' => 'string',
                'confidence' => 'number',
                'warnings' => 'array',
                'requiresreview' => 'boolean',
            ],
            'instructions' => [
                'Draft a proposed support reply.',
                'The reply must be reviewed by a human before sending.',
                'Never claim that a payment succeeded unless CRM context confirms it.',
                'Never promise access, refund or cancellation unless CRM context confirms that the action has already occurred.',
                'Never expose internal IDs, scores, provider identifiers or technical notes.',
                'Ask for missing information when required.',
                'Use a polite and professional CampusFR support tone.',
                'Do not include a subject prefix twice.',
                'Set requiresreview to true.',
            ],
            'forbiddenactions' => [
                'send_reply',
                'refund',
                'grant_access',
                'cancel_payment',
                'suspend_account',
            ],
        ];
    }
}