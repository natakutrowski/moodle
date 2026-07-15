<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCategory;

final class InboxCategoryPromptBuilder {

    public const VERSION = 'category-v1';

    public function constraints(): array {
        return [
            'outputformat' => 'json',
            'allowedcategories' =>
                InboxAiCategory::values(),
            'schema' => [
                'category' => 'string',
                'secondarycategories' => 'array',
                'confidence' => 'number',
                'signals' => 'array',
            ],
            'instructions' => [
                'Select one primary category.',
                'Return at most two secondary categories.',
                'Use other when evidence is insufficient.',
                'Do not infer that a payment succeeded unless CRM data confirms it.',
                'Do not perform any business action.',
            ],
        ];
    }
}