<?php

namespace local_subscriptions\crm\intelligence\recommendations\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationLifecycle;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\intelligence\recommendations\repositories\RecommendationRepository;
use local_subscriptions\crm\intelligence\recommendations\logging\RecommendationAdminEventLogger;

/**
 * Permission-independent recommendation lifecycle service.
 *
 * Moodle pages and external functions remain responsible for capability and
 * sesskey validation before calling this service.
 */
final class RecommendationLifecycleService {

    public function __construct(
        private readonly RecommendationRepository $repository =
            new RecommendationRepository(),
        private readonly RecommendationLifecycle $lifecycle =
            new RecommendationLifecycle(),
        private readonly RecommendationAdminEventLogger $logger =
            new RecommendationAdminEventLogger()
    ) {
    }

    /**
     * Accept a proposed recommendation.
     */
    public function accept(
        int $recommendationid,
        int $actorid
    ): \stdClass {
        $record = $this->transition(
            $recommendationid,
            RecommendationStatus::ACCEPTED,
            $actorid
        );

        $this->logger->accepted($record);

        return $record;
    }

    /**
     * Dismiss a recommendation.
     */
    public function dismiss(
        int $recommendationid,
        int $actorid,
        string $reason
    ): \stdClass {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException(
                'A recommendation dismissal reason is required.'
            );
        }

        if (\core_text::strlen($reason) > 100) {
            throw new \InvalidArgumentException(
                'Recommendation dismissal reason is too long.'
            );
        }

        if (
            preg_match(
                '/^[a-z][a-z0-9_.-]{1,99}$/',
                $reason
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'Recommendation dismissal reason must be a stable technical value.'
            );
        }

        $record = $this->transition(
            $recommendationid,
            RecommendationStatus::DISMISSED,
            $actorid,
            $reason
        );

        $this->logger->dismissed($record);

        return $record;
    }

    /**
     * Mark the recommendation action as completed.
     */
    public function complete(
        int $recommendationid,
        int $actorid
    ): \stdClass {
        $record = $this->transition(
            $recommendationid,
            RecommendationStatus::COMPLETED,
            $actorid
        );

        $this->logger->completed($record);

        return $record;
    }

    /**
     * Expire one recommendation.
     */
    public function expire(
        int $recommendationid,
        int $actorid
    ): \stdClass {
        $record = $this->transition(
            $recommendationid,
            RecommendationStatus::EXPIRED,
            $actorid
        );

        $this->logger->expired($record);

        return $record;
    }

    /**
     * Expire all due recommendations.
     */
    public function expire_due(
        ?int $now = null
    ): int {
        return $this->repository->expire_due(
            $now ?? time()
        );
    }

    /**
     * Execute one validated transition.
     */
    private function transition(
        int $recommendationid,
        string $newstatus,
        int $actorid,
        ?string $dismissalreason = null
    ): \stdClass {
        if ($recommendationid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation ID must be greater than zero.'
            );
        }

        if ($actorid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation actor ID must be greater than zero.'
            );
        }

        $current = $this->repository->get(
            $recommendationid
        );

        $this->lifecycle->require_transition(
            (string)$current->status,
            $newstatus
        );

        return $this->repository->transition(
            $recommendationid,
            $newstatus,
            $actorid,
            $dismissalreason
        );
    }
}