<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;

/**
 * Immutable Customer Success plan representation.
 */
final class CustomerSuccessPlan {

    /**
     * @param CustomerSuccessPlanStep[] $steps
     */
    public function __construct(
        public readonly int $id,
        public readonly string $reference,
        public readonly int $userid,
        public readonly string $objectivekey,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $source,
        public readonly string $priority,
        public readonly ?int $assignedteamid,
        public readonly ?int $assigneduserid,
        public readonly ?int $targetdate,
        public readonly ?string $blockedreason,
        public readonly ?string $fingerprint,
        public readonly ?int $activatedat,
        public readonly ?int $completedat,
        public readonly int $timecreated,
        public readonly int $timemodified,
        public readonly int $createdby,
        public readonly int $modifiedby,
        public readonly array $steps = []
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan ID must be greater than zero.'
            );
        }

        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan user ID must be greater than zero.'
            );
        }

        if (trim($this->reference) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan reference is required.'
            );
        }

        if (trim($this->objectivekey) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan objective key is required.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan title is required.'
            );
        }

        if (
            !CustomerSuccessPlanStatus::is_valid(
                $this->status
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan status.'
            );
        }

        if (
            !CustomerSuccessPlanSource::is_valid(
                $this->source
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan source.'
            );
        }

        foreach ($this->steps as $step) {
            if (
                !$step instanceof
                CustomerSuccessPlanStep
            ) {
                throw new \InvalidArgumentException(
                    'Customer Success plan steps must contain CustomerSuccessPlanStep objects.'
                );
            }
        }
    }

    public function step_count(): int {
        return count($this->steps);
    }

    public function completed_step_count(): int {
        return count(array_filter(
            $this->steps,
            static fn(
                CustomerSuccessPlanStep $step
            ): bool => $step->is_completed()
        ));
    }

    public function blocked_step_count(): int {
        return count(array_filter(
            $this->steps,
            static fn(
                CustomerSuccessPlanStep $step
            ): bool => $step->is_blocked()
        ));
    }

    public function progress_percentage(): float {
        $count = $this->step_count();

        if ($count === 0) {
            return 0.0;
        }

        return round(
            (
                $this->completed_step_count() /
                $count
            ) * 100,
            2
        );
    }

    public function is_open(): bool {
        return CustomerSuccessPlanStatus::is_open(
            $this->status
        );
    }

    public function is_blocked(): bool {
        return
            $this->blockedreason !== null ||
            $this->blocked_step_count() > 0;
    }
}