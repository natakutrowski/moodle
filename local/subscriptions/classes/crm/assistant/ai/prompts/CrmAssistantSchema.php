<?php

namespace local_subscriptions\crm\assistant\ai\prompts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\dto\CrmAssistantReference;

/**
 * Structured Output schema for CRM Assistant answers.
 */
final class CrmAssistantSchema {

    public function name(): string {
        return 'campusfr_crm_assistant_answer';
    }

    public function schema(): array {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'answer' => [
                    'type' => 'string',
                    'maxLength' => 6000,
                ],
                'keypoints' =>
                    $this->string_array(12),
                'suggestedactions' =>
                    $this->string_array(10),
                'warnings' =>
                    $this->string_array(10),
                'references' => [
                    'type' => 'array',
                    'maxItems' => 20,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' =>
                            false,
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' =>
                                    CrmAssistantReference::types(),
                            ],
                            'id' => [
                                'type' => 'integer',
                                'minimum' => 1,
                            ],
                            'label' => [
                                'type' => 'string',
                                'maxLength' => 500,
                            ],
                            'reason' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                                'maxLength' => 1000,
                            ],
                        ],
                        'required' => [
                            'type',
                            'id',
                            'label',
                            'reason',
                        ],
                    ],
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'requiresreview' => [
                    'type' => 'boolean',
                    'const' => true,
                ],
            ],
            'required' => [
                'answer',
                'keypoints',
                'suggestedactions',
                'warnings',
                'references',
                'confidence',
                'requiresreview',
            ],
        ];
    }

    private function string_array(
        int $maxitems
    ): array {
        return [
            'type' => 'array',
            'maxItems' => $maxitems,
            'items' => [
                'type' => 'string',
                'maxLength' => 1500,
            ],
        ];
    }
}