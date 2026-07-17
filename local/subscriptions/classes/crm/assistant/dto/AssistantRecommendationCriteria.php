<?php

namespace local_subscriptions\crm\assistant\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\intelligence\recommendations\RecommendationTarget;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;

/**
 * Immutable filters used by the CRM Assistant workspace.
 */
final class AssistantRecommendationCriteria {

    public const SCOPE_ACTIVE = 'active';
    public const SCOPE_ALL = 'all';

    public function __construct(
        public readonly string $scope = self::SCOPE_ACTIVE,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?string $prioritylevel = null,
        public readonly ?int $userid = null,
        public readonly int $limit = 100,
        public readonly int $offset = 0
    ) {
        if (
            !in_array(
                $this->scope,
                [
                    self::SCOPE_ACTIVE,
                    self::SCOPE_ALL,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant scope.'
            );
        }

        if (
            $this->status !== null &&
            !RecommendationStatus::is_valid(
                $this->status
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant recommendation status.'
            );
        }

        if (
            $this->type !== null &&
            !RecommendationType::is_valid(
                $this->type
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant recommendation type.'
            );
        }

        if (
            $this->prioritylevel !== null &&
            !in_array(
                $this->prioritylevel,
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'critical',
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant priority level.'
            );
        }

        if (
            $this->userid !== null &&
            $this->userid <= 0
        ) {
            throw new \InvalidArgumentException(
                'CRM Assistant user ID must be greater than zero.'
            );
        }

        if ($this->limit < 1 || $this->limit > 500) {
            throw new \InvalidArgumentException(
                'CRM Assistant result limit must be between 1 and 500.'
            );
        }

        if ($this->offset < 0) {
            throw new \InvalidArgumentException(
                'CRM Assistant offset cannot be negative.'
            );
        }
    }

    public function active_only(): bool {
        return $this->scope === self::SCOPE_ACTIVE;
    }

    public function targettype(): ?string {
        return $this->userid !== null
            ? RecommendationTarget::USER
            : null;
    }
}