<?php

namespace local_subscriptions\crm\assistant\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;

/**
 * Presentation DTO for one persistent recommendation.
 */
final class AssistantRecommendation {

    public function __construct(
        public readonly int $id,
        public readonly string $fingerprint,
        public readonly string $key,
        public readonly string $type,
        public readonly string $presentationtype,
        public readonly int $priority,
        public readonly string $prioritylevel,
        public readonly string $status,
        public readonly ?string $targettype,
        public readonly ?int $targetid,
        public readonly ?string $targetname,
        public readonly array $sources,
        public readonly array $evidence,
        public readonly array $actions,
        public readonly int $generatedat,
        public readonly ?int $validuntil,
        public readonly int $firstdetectedat,
        public readonly int $lastdetectedat,
        public readonly ?string $dismissalreason = null
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException(
                'Assistant recommendation ID must be greater than zero.'
            );
        }
    }

    public function is_active(): bool {
        if (
            !RecommendationStatus::is_active(
                $this->status
            )
        ) {
            return false;
        }

        return
            $this->validuntil === null ||
            $this->validuntil > time();
    }

    public function is_actionable(): bool {
        return
            $this->status === RecommendationStatus::PROPOSED ||
            $this->status === RecommendationStatus::ACCEPTED;
    }

    public function is_user_target(): bool {
        return
            $this->targettype === 'user' &&
            $this->targetid !== null;
    }

    public function evidence_count(): int {
        return count($this->evidence);
    }

    public function source_count(): int {
        return count($this->sources);
    }

    public function primary_action(): ?array {
        foreach ($this->actions as $action) {
            if (!empty($action['primary'])) {
                return $action;
            }
        }

        return $this->actions[0] ?? null;
    }
}