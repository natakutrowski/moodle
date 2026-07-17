<?php

namespace local_subscriptions\crm\work\intelligence\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Existing Work Item that may duplicate a proposed item.
 */
final class WorkItemDuplicateCandidate {

    public function __construct(
        public readonly int $workitemid,
        public readonly string $reference,
        public readonly string $title,
        public readonly string $type,
        public readonly string $priority,
        public readonly string $status,
        public readonly int $similarityscore,
        public readonly array $reasons = []
    ) {
        if ($this->workitemid <= 0) {
            throw new \InvalidArgumentException(
                'Duplicate candidate Work Item ID must be greater than zero.'
            );
        }

        if (
            $this->similarityscore < 0 ||
            $this->similarityscore > 100
        ) {
            throw new \InvalidArgumentException(
                'Duplicate candidate similarity must be between 0 and 100.'
            );
        }
    }

    public function is_probable_duplicate(): bool {
        return $this->similarityscore >= 75;
    }

    public function is_possible_duplicate(): bool {
        return $this->similarityscore >= 50;
    }

    public function to_object(): \stdClass {
        return (object)[
            'workitemid' => $this->workitemid,
            'reference' => $this->reference,
            'title' => $this->title,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'similarityscore' =>
                $this->similarityscore,
            'reasons' => $this->reasons,
            'probableduplicate' =>
                $this->is_probable_duplicate(),
        ];
    }
}