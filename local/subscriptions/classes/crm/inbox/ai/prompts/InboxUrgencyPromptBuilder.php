<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiUrgency;

final class InboxUrgencyPromptBuilder {

    public const VERSION = 'urgency-v1';

    public function constraints(): array {
        return [
            'outputformat' => 'json',
            'allowedvalues' =>
                InboxAiUrgency::values(),
            'schema' => [
                'urgency' => 'string',
                'confidence' => 'number',
                'signals' => 'array',
            ],
            'rules' => [
                'critical' => [
                    'security compromise',
                    'fraud',
                    'duplicate charge',
                    'legal threat',
                    'immediate safety issue',
                ],
                'high' => [
                    'confirmed payment without access',
                    'blocked access to purchased content',
                    'time-sensitive examination or deadline',
                ],
                'normal' => [
                    'standard support request',
                    'technical issue without immediate impact',
                ],
                'low' => [
                    'general information',
                    'feedback',
                    'non-urgent commercial question',
                ],
            ],
            'instructions' => [
                'Classify urgency only.',
                'Do not perform or recommend an administrative action.',
                'Do not mark a message critical only because it contains the word urgent.',
                'Return concise factual signals.',
            ],
        ];
    }
}