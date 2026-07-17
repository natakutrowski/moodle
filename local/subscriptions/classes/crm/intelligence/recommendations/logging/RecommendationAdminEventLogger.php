<?php

namespace local_subscriptions\crm\intelligence\recommendations\logging;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;

/**
 * Logs recommendation lifecycle events to the unified administration log.
 */
final class RecommendationAdminEventLogger {

    public function created(
        \stdClass $recommendation
    ): void {
        $this->log(
            AdminEvents::RECOMMENDATION_CREATED,
            $recommendation
        );
    }

    public function refreshed(
        \stdClass $recommendation
    ): void {
        $this->log(
            AdminEvents::RECOMMENDATION_REFRESHED,
            $recommendation
        );
    }

    public function accepted(
        \stdClass $recommendation
    ): void {
        $this->log(
            AdminEvents::RECOMMENDATION_ACCEPTED,
            $recommendation
        );
    }

    public function dismissed(
        \stdClass $recommendation
    ): void {
        $this->log(
            AdminEvents::RECOMMENDATION_DISMISSED,
            $recommendation,
            [
                'dismissalreason' =>
                    $recommendation->dismissalreason ?? null,
            ]
        );
    }

    public function completed(
        \stdClass $recommendation
    ): void {
        $this->log(
            AdminEvents::RECOMMENDATION_COMPLETED,
            $recommendation
        );
    }

    public function expired(
        \stdClass $recommendation
    ): void {
        $this->log(
            AdminEvents::RECOMMENDATION_EXPIRED,
            $recommendation
        );
    }

    /**
     * Write one unified AdminLog entry.
     */
    private function log(
        string $event,
        \stdClass $recommendation,
        array $details = []
    ): void {
        $targetuserid = null;

        if (
            ($recommendation->targettype ?? null) === 'user' &&
            !empty($recommendation->targetid)
        ) {
            $targetuserid =
                (int)$recommendation->targetid;
        }

        $details = array_merge(
            [
                'recommendationid' =>
                    (int)$recommendation->id,

                'recommendationkey' =>
                    (string)$recommendation->recommendationkey,

                'recommendationtype' =>
                    (string)$recommendation->recommendationtype,

                'status' =>
                    (string)$recommendation->status,

                'priority' =>
                    (int)$recommendation->priority,

                'targettype' =>
                    $recommendation->targettype ?? null,

                'targetid' =>
                    !empty($recommendation->targetid)
                        ? (int)$recommendation->targetid
                        : null,
            ],
            $details
        );

        AdminLog::log(
            $event,
            $targetuserid,
            'recommendation',
            (int)$recommendation->id,
            $details
        );
    }
}