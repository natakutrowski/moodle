<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\domain\InboxAiCategory;
use local_subscriptions\crm\inbox\ai\domain\InboxAiUrgency;

final class OpenAiSchemaRegistry {

    public function name(
        string $capability
    ): string {
        return 'campusfr_inbox_' .
            preg_replace(
                '/[^a-z0-9_]+/',
                '_',
                strtolower($capability)
            );
    }

    public function schema(
        string $capability
    ): array {
        return match ($capability) {
            InboxAiCapability::LANGUAGE_DETECTION =>
                $this->language(),

            InboxAiCapability::URGENCY_CLASSIFICATION =>
                $this->urgency(),

            InboxAiCapability::CATEGORIZATION =>
                $this->category(),

            InboxAiCapability::SUMMARY =>
                $this->summary(),

            InboxAiCapability::TRANSLATION =>
                $this->translation(),

            InboxAiCapability::REPLY_SUGGESTION =>
                $this->reply(),

            InboxAiCapability::REQUEST_EXTRACTION =>
                $this->requests(),

            InboxAiCapability::CRM_RELEVANCE =>
                $this->relevance(),

            default =>
                throw new \invalid_parameter_exception(
                    'Unsupported OpenAI schema capability.'
                ),
        };
    }

    private function language(): array {
        return $this->object(
            [
                'language' => [
                    'type' => 'string',
                    'maxLength' => 16,
                ],
                'confidence' =>
                    $this->confidence(),
            ],
            ['language', 'confidence']
        );
    }

    private function urgency(): array {
        return $this->object(
            [
                'urgency' => [
                    'type' => 'string',
                    'enum' =>
                        InboxAiUrgency::values(),
                ],
                'signals' =>
                    $this->string_array(10),
                'confidence' =>
                    $this->confidence(),
            ],
            [
                'urgency',
                'signals',
                'confidence',
            ]
        );
    }

    private function category(): array {
        return $this->object(
            [
                'category' => [
                    'type' => 'string',
                    'enum' =>
                        InboxAiCategory::values(),
                ],
                'secondarycategories' => [
                    'type' => 'array',
                    'maxItems' => 2,
                    'items' => [
                        'type' => 'string',
                        'enum' =>
                            InboxAiCategory::values(),
                    ],
                ],
                'signals' =>
                    $this->string_array(10),
                'confidence' =>
                    $this->confidence(),
            ],
            [
                'category',
                'secondarycategories',
                'signals',
                'confidence',
            ]
        );
    }

    private function summary(): array {
        return $this->object(
            [
                'summary' => [
                    'type' => 'string',
                    'maxLength' => 4000,
                ],
                'keypoints' =>
                    $this->string_array(10),
                'pendingquestions' =>
                    $this->string_array(10),
                'customerrequests' =>
                    $this->string_array(10),
                'language' => [
                    'type' => 'string',
                    'maxLength' => 16,
                ],
                'confidence' =>
                    $this->confidence(),
            ],
            [
                'summary',
                'keypoints',
                'pendingquestions',
                'customerrequests',
                'language',
                'confidence',
            ]
        );
    }

    private function translation(): array {
        return $this->object(
            [
                'translatedtext' => [
                    'type' => 'string',
                    'maxLength' => 20000,
                ],
                'sourcelanguage' => [
                    'type' => 'string',
                    'maxLength' => 16,
                ],
                'targetlanguage' => [
                    'type' => 'string',
                    'maxLength' => 16,
                ],
                'confidence' =>
                    $this->confidence(),
            ],
            [
                'translatedtext',
                'sourcelanguage',
                'targetlanguage',
                'confidence',
            ]
        );
    }

    private function reply(): array {
        return $this->object(
            [
                'subject' => [
                    'type' => 'string',
                    'maxLength' => 500,
                ],
                'body' => [
                    'type' => 'string',
                    'maxLength' => 12000,
                ],
                'language' => [
                    'type' => 'string',
                    'maxLength' => 16,
                ],
                'tone' => [
                    'type' => 'string',
                    'enum' => [
                        'professional',
                        'friendly',
                        'empathetic',
                        'concise',
                    ],
                ],
                'confidence' =>
                    $this->confidence(),
                'warnings' =>
                    $this->string_array(20),
                'requiresreview' => [
                    'type' => 'boolean',
                    'const' => true,
                ],
            ],
            [
                'subject',
                'body',
                'language',
                'tone',
                'confidence',
                'warnings',
                'requiresreview',
            ]
        );
    }

    private function requests(): array {
        return $this->object(
            [
                'requests' => [
                    'type' => 'array',
                    'maxItems' => 10,
                    'items' => $this->object(
                        [
                            'type' => [
                                'type' => 'string',
                                'maxLength' => 64,
                            ],
                            'description' => [
                                'type' => 'string',
                                'maxLength' => 1000,
                            ],
                            'entities' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => [
                                        'string',
                                        'number',
                                        'boolean',
                                        'null',
                                    ],
                                ],
                            ],
                            'confidence' =>
                                $this->confidence(),
                        ],
                        [
                            'type',
                            'description',
                            'entities',
                            'confidence',
                        ]
                    ),
                ],
                'confidence' =>
                    $this->confidence(),
            ],
            ['requests', 'confidence']
        );
    }

    private function relevance(): array {
        return $this->object(
            [
                'relevance' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'reasons' =>
                    $this->string_array(10),
                'confidence' =>
                    $this->confidence(),
            ],
            [
                'relevance',
                'reasons',
                'confidence',
            ]
        );
    }

    private function object(
        array $properties,
        array $required
    ): array {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => $properties,
            'required' => $required,
        ];
    }

    private function confidence(): array {
        return [
            'type' => 'number',
            'minimum' => 0,
            'maximum' => 1,
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
                'maxLength' => 1000,
            ],
        ];
    }
}