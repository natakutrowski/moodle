<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;

/**
 * Immutable Customer Success plan step representation.
 */
final class CustomerSuccessPlanStep {

    public function __construct(
        public readonly int $id,
        public readonly int $planid,
        public readonly int $position,
        public readonly string $stepkey,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $priority,
        public readonly ?int $dependsonstepid,
        public readonly ?string $blockedreason,
        public readonly ?string $relationtype,
        public readonly ?int $relationid,
        public readonly ?int $assignedteamid,
        public readonly ?int $assigneduserid,
        public readonly ?int $dueat,
        public readonly ?int $startedat,
        public readonly ?int $completedat,
        public readonly int $timecreated,
        public readonly int $timemodified,
        public readonly int $createdby,
        public readonly int $modifiedby
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan step ID must be greater than zero.'
            );
        }

        if ($this->planid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan step plan ID must be greater than zero.'
            );
        }

        if ($this->position <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan step position must be greater than zero.'
            );
        }

        if (trim($this->stepkey) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan step key is required.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan step title is required.'
            );
        }

        if (
            !CustomerSuccessPlanStepStatus::is_valid(
                $this->status
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan step status.'
            );
        }

        if (
            $this->relationtype !== null &&
            !CustomerSuccessPlanRelationType::is_valid(
                $this->relationtype
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan relation type.'
            );
        }

        if (
            $this->relationtype === null &&
            $this->relationid !== null
        ) {
            throw new \InvalidArgumentException(
                'A Customer Success plan relation ID requires a relation type.'
            );
        }

        if (
            $this->relationtype !== null &&
            (
                $this->relationid === null ||
                $this->relationid <= 0
            )
        ) {
            throw new \InvalidArgumentException(
                'A Customer Success plan relation type requires a valid relation ID.'
            );
        }
    }

    public function is_completed(): bool {
        return in_array(
            $this->status,
            [
                CustomerSuccessPlanStepStatus::COMPLETED,
                CustomerSuccessPlanStepStatus::SKIPPED,
            ],
            true
        );
    }

    public function is_blocked(): bool {
        return $this->status ===
            CustomerSuccessPlanStepStatus::BLOCKED;
    }

    public function is_actionable(): bool {
        return in_array(
            $this->status,
            [
                CustomerSuccessPlanStepStatus::READY,
                CustomerSuccessPlanStepStatus::IN_PROGRESS,
            ],
            true
        );
    }
}