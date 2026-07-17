<?php

namespace local_subscriptions\crm\assistant\ai\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Structured answer produced by the conversational CRM Assistant.
 */
final class CrmAssistantAnswer {

    /**
     * @param string[] $keypoints
     * @param string[] $suggestedactions
     * @param string[] $warnings
     * @param CrmAssistantReference[] $references
     */
    public function __construct(
        public readonly string $answer,
        public readonly array $keypoints,
        public readonly array $suggestedactions,
        public readonly array $warnings,
        public readonly array $references,
        public readonly float $confidence,
        public readonly bool $requiresreview = true,
        public readonly array $metadata = []
    ) {
        if (trim($this->answer) === '') {
            throw new \InvalidArgumentException(
                'CRM Assistant answer cannot be empty.'
            );
        }

        if (
            $this->confidence < 0 ||
            $this->confidence > 1
        ) {
            throw new \InvalidArgumentException(
                'CRM Assistant confidence must be between 0 and 1.'
            );
        }

        if (!$this->requiresreview) {
            throw new \InvalidArgumentException(
                'CRM Assistant answers must always require human review.'
            );
        }

        foreach ($this->references as $reference) {
            if (
                !$reference instanceof
                CrmAssistantReference
            ) {
                throw new \InvalidArgumentException(
                    'CRM Assistant references must contain CrmAssistantReference objects.'
                );
            }
        }
    }

    public function to_object(): \stdClass {
        return (object)[
            'answer' => $this->answer,
            'keypoints' => $this->keypoints,
            'suggestedactions' =>
                $this->suggestedactions,
            'warnings' => $this->warnings,
            'references' => array_map(
                static fn(
                    CrmAssistantReference $reference
                ): \stdClass =>
                    $reference->to_object(),
                $this->references
            ),
            'confidence' => $this->confidence,
            'requiresreview' =>
                $this->requiresreview,
            'metadata' => $this->metadata,
        ];
    }
}