<?php

namespace local_subscriptions\crm\intelligence\recommendations\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationHistoryAction;
use local_subscriptions\crm\intelligence\recommendations\RecommendationRecordMapper;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;

/**
 * Persistence repository for CRM recommendations.
 *
 * All recommendation SQL remains contained in this class.
 */
final class RecommendationRepository {

    private const TABLE =
        'local_subscriptions_recommendation';

    private const HISTORY_TABLE =
        'local_subscriptions_recommendation_history';

    public function __construct(
        private readonly RecommendationRecordMapper $mapper =
            new RecommendationRecordMapper()
    ) {
    }

    /**
     * Create or refresh a persistent recommendation.
     */
    public function upsert(
        Recommendation $recommendation
    ): \stdClass {
        global $DB;

        $now = time();
        $fingerprint = $recommendation->fingerprint();

        $existing = $DB->get_record(
            self::TABLE,
            [
                'fingerprint' => $fingerprint,
            ],
            '*',
            IGNORE_MISSING
        );

        if (!$existing) {
            $record = $this->mapper->to_create_record(
                $recommendation,
                $now
            );

            $record->id = $DB->insert_record(
                self::TABLE,
                $record
            );

            $this->add_history(
                (int)$record->id,
                null,
                RecommendationHistoryAction::CREATED,
                null,
                RecommendationStatus::PROPOSED,
                [
                    'fingerprint' => $fingerprint,
                    'priority' =>
                        $recommendation->priority,
                ]
            );

            return $this->get((int)$record->id);
        }

        $oldstatus = (string)$existing->status;
        $newstatus = $this->resolve_refresh_status(
            $oldstatus
        );

        $record = $this->mapper->to_refresh_record(
            (int)$existing->id,
            $recommendation,
            $newstatus,
            $now
        );

        if ($newstatus === RecommendationStatus::PROPOSED) {
            $record->dismissedby = null;
            $record->dismissedat = null;
            $record->dismissalreason = null;
            $record->completedby = null;
            $record->completedat = null;
        }

        $DB->update_record(
            self::TABLE,
            $record
        );

        $action = $newstatus !== $oldstatus
            ? RecommendationHistoryAction::REOPENED
            : RecommendationHistoryAction::REFRESHED;

        $this->add_history(
            (int)$existing->id,
            null,
            $action,
            $oldstatus,
            $newstatus,
            [
                'priority' =>
                    $recommendation->priority,
                'lastdetectedat' => $now,
            ]
        );

        return $this->get((int)$existing->id);
    }

    /**
     * Retrieve one recommendation.
     */
    public function get(int $recommendationid): \stdClass {
        global $DB;

        return $DB->get_record(
            self::TABLE,
            [
                'id' => $recommendationid,
            ],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Retrieve one recommendation without throwing.
     */
    public function find(
        int $recommendationid
    ): ?\stdClass {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'id' => $recommendationid,
            ],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Retrieve one recommendation by fingerprint.
     */
    public function find_by_fingerprint(
        string $fingerprint
    ): ?\stdClass {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'fingerprint' => $fingerprint,
            ],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Retrieve active recommendations for a CRM target.
     */
    public function get_active_for_target(
        string $targettype,
        int $targetid
    ): array {
        global $DB;

        [$statussql, $params] = $DB->get_in_or_equal(
            RecommendationStatus::active(),
            SQL_PARAMS_NAMED,
            'status'
        );

        $params['targettype'] = $targettype;
        $params['targetid'] = $targetid;
        $params['now'] = time();

        return array_values($DB->get_records_sql(
            "SELECT r.*
               FROM {" . self::TABLE . "} r
              WHERE r.targettype = :targettype
                AND r.targetid = :targetid
                AND r.status {$statussql}
                AND (
                    r.validuntil IS NULL
                    OR r.validuntil > :now
                )
           ORDER BY r.priority DESC,
                    r.lastdetectedat DESC,
                    r.id DESC",
            $params
        ));
    }

    /**
     * Retrieve all active recommendations.
     */
    public function get_active(
        int $limit = 100
    ): array {
        global $DB;

        $limit = max(1, min(1000, $limit));

        [$statussql, $params] = $DB->get_in_or_equal(
            RecommendationStatus::active(),
            SQL_PARAMS_NAMED,
            'status'
        );

        $params['now'] = time();

        return array_values($DB->get_records_sql(
            "SELECT r.*
               FROM {" . self::TABLE . "} r
              WHERE r.status {$statussql}
                AND (
                    r.validuntil IS NULL
                    OR r.validuntil > :now
                )
           ORDER BY r.priority DESC,
                    r.lastdetectedat DESC,
                    r.id DESC",
            $params,
            0,
            $limit
        ));
    }

    /**
     * Change lifecycle status atomically.
     */
    public function transition(
        int $recommendationid,
        string $newstatus,
        int $actorid,
        ?string $dismissalreason = null
    ): \stdClass {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $current = $this->get(
            $recommendationid
        );

        $oldstatus = (string)$current->status;
        $now = time();

        $record = (object)[
            'id' => $recommendationid,
            'status' => $newstatus,
            'timemodified' => $now,
        ];

        if ($newstatus === RecommendationStatus::ACCEPTED) {
            $record->acceptedby = $actorid;
            $record->acceptedat = $now;
        }

        if ($newstatus === RecommendationStatus::DISMISSED) {
            $record->dismissedby = $actorid;
            $record->dismissedat = $now;
            $record->dismissalreason =
                $dismissalreason;
        }

        if ($newstatus === RecommendationStatus::COMPLETED) {
            $record->completedby = $actorid;
            $record->completedat = $now;
        }

        if ($newstatus === RecommendationStatus::EXPIRED) {
            $record->validuntil =
                $current->validuntil ?? $now;
        }

        $DB->update_record(
            self::TABLE,
            $record
        );

        $this->add_history(
            $recommendationid,
            $actorid,
            $this->history_action_for_status(
                $newstatus
            ),
            $oldstatus,
            $newstatus,
            $dismissalreason !== null
                ? [
                    'reason' => $dismissalreason,
                ]
                : []
        );

        $transaction->allow_commit();

        return $this->get(
            $recommendationid
        );
    }

    /**
     * Expire active recommendations whose validity ended.
     */
    public function expire_due(
        int $now
    ): int {
        global $DB;

        [$statussql, $params] = $DB->get_in_or_equal(
            RecommendationStatus::active(),
            SQL_PARAMS_NAMED,
            'status'
        );

        $params['now'] = $now;

        $records = $DB->get_records_sql(
            "SELECT r.id, r.status
               FROM {" . self::TABLE . "} r
              WHERE r.status {$statussql}
                AND r.validuntil IS NOT NULL
                AND r.validuntil <= :now",
            $params
        );

        foreach ($records as $record) {
            $DB->set_field(
                self::TABLE,
                'status',
                RecommendationStatus::EXPIRED,
                [
                    'id' => (int)$record->id,
                ]
            );

            $DB->set_field(
                self::TABLE,
                'timemodified',
                $now,
                [
                    'id' => (int)$record->id,
                ]
            );

            $this->add_history(
                (int)$record->id,
                null,
                RecommendationHistoryAction::EXPIRED,
                (string)$record->status,
                RecommendationStatus::EXPIRED,
                [
                    'expiredat' => $now,
                ]
            );
        }

        return count($records);
    }

    /**
     * Retrieve immutable history.
     */
    public function get_history(
        int $recommendationid
    ): array {
        global $DB;

        return array_values($DB->get_records(
            self::HISTORY_TABLE,
            [
                'recommendationid' =>
                    $recommendationid,
            ],
            'timecreated ASC, id ASC'
        ));
    }

    /**
     * Add an immutable lifecycle history entry.
     */
    public function add_history(
        int $recommendationid,
        ?int $actorid,
        string $action,
        ?string $oldstatus,
        ?string $newstatus,
        array $metadata = []
    ): int {
        global $DB;

        if (
            !RecommendationHistoryAction::is_valid(
                $action
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid recommendation history action.'
            );
        }

        return (int)$DB->insert_record(
            self::HISTORY_TABLE,
            (object)[
                'recommendationid' =>
                    $recommendationid,
                'actorid' => $actorid,
                'action' => $action,
                'oldstatus' => $oldstatus,
                'newstatus' => $newstatus,
                'metadatajson' => $metadata !== []
                    ? json_encode(
                        $metadata,
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR
                    )
                    : null,
                'timecreated' => time(),
            ]
        );
    }

    /**
     * Resolve status when an existing recommendation reappears.
     */
    private function resolve_refresh_status(
        string $status
    ): string {
        if (
            in_array(
                $status,
                [
                    RecommendationStatus::DISMISSED,
                    RecommendationStatus::COMPLETED,
                    RecommendationStatus::EXPIRED,
                ],
                true
            )
        ) {
            return RecommendationStatus::PROPOSED;
        }

        return $status;
    }

    /**
     * Resolve history action from status.
     */
    private function history_action_for_status(
        string $status
    ): string {
        return match ($status) {
            RecommendationStatus::ACCEPTED =>
                RecommendationHistoryAction::ACCEPTED,

            RecommendationStatus::DISMISSED =>
                RecommendationHistoryAction::DISMISSED,

            RecommendationStatus::COMPLETED =>
                RecommendationHistoryAction::COMPLETED,

            RecommendationStatus::EXPIRED =>
                RecommendationHistoryAction::EXPIRED,

            default => throw new \InvalidArgumentException(
                'Unsupported recommendation lifecycle status.'
            ),
        };
    }
}