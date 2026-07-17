<?php

namespace local_subscriptions\crm\assistant\ai\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\dto\AssistantRecommendationCriteria;
use local_subscriptions\crm\assistant\repositories\AssistantRecommendationRepository;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\work\domain\WorkItemStatus;

/**
 * Read-only repository for conversational Assistant context.
 *
 * All SQL used by the conversational assistant remains in this repository.
 */
final class CrmAssistantContextRepository {

    public function __construct(
        private readonly AssistantRecommendationRepository $recommendations =
            new AssistantRecommendationRepository()
    ) {
    }

    public function global_recommendations(
        int $limit = 50
    ): array {
        return $this->recommendations->search(
            new AssistantRecommendationCriteria(
                scope:
                    AssistantRecommendationCriteria::SCOPE_ACTIVE,
                limit: max(1, min(100, $limit))
            )
        );
    }

    public function user_recommendations(
        int $userid,
        int $limit = 30
    ): array {
        return $this->recommendations->get_for_user(
            $userid,
            max(1, min(50, $limit))
        );
    }

    public function recommendation(
        int $recommendationid
    ): \local_subscriptions\crm\assistant\dto\AssistantRecommendation {
        return $this->recommendations->get(
            $recommendationid
        );
    }

    public function user_summary(
        int $userid
    ): ?\stdClass {
        global $DB;

        $record = $DB->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
            ],
            'id,firstname,lastname,lang,lastaccess,suspended',
            IGNORE_MISSING
        );

        if (!$record) {
            return null;
        }

        return (object)[
            'id' => (int)$record->id,
            'fullname' => fullname($record),
            'language' =>
                (string)$record->lang,
            'lastaccess' =>
                (int)$record->lastaccess,
            'suspended' =>
                (bool)$record->suspended,
        ];
    }

    public function active_work_items(
        ?int $userid = null,
        int $limit = 30
    ): array {
        global $DB;

        [$statussql, $params] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'assistantworkstatus'
            );

        $conditions = [
            "item.status {$statussql}",
        ];

        if ($userid !== null) {
            $conditions[] =
                'item.targetuserid = :assistantworkuserid';

            $params['assistantworkuserid'] =
                $userid;
        }

        return array_values($DB->get_records_sql(
            "SELECT
                    item.id,
                    item.reference,
                    item.title,
                    item.type,
                    item.priority,
                    item.status,
                    item.targetuserid,
                    item.assignedteamid,
                    item.assigneduserid,
                    item.dueat,
                    item.timemodified
               FROM {local_subscriptions_work_item} item
              WHERE " . implode(
                  ' AND ',
                  $conditions
              ) . "
           ORDER BY
                    CASE item.priority
                        WHEN 'critical' THEN 5
                        WHEN 'urgent' THEN 4
                        WHEN 'high' THEN 3
                        WHEN 'normal' THEN 2
                        ELSE 1
                    END DESC,
                    item.dueat ASC,
                    item.timemodified DESC",
            $params,
            0,
            max(1, min(100, $limit))
        ));
    }

    public function recommendation_counts():
        \stdClass {
        global $DB;

        $now = time();

        return $DB->get_record_sql(
            "SELECT
                    COUNT(1) AS activecount,
                    SUM(
                        CASE
                            WHEN r.prioritylevel = 'critical'
                            THEN 1 ELSE 0
                        END
                    ) AS criticalcount,
                    SUM(
                        CASE
                            WHEN r.prioritylevel = 'urgent'
                            THEN 1 ELSE 0
                        END
                    ) AS urgentcount,
                    COUNT(
                        DISTINCT CASE
                            WHEN r.targettype = 'user'
                            THEN r.targetid
                            ELSE NULL
                        END
                    ) AS usercount
               FROM {local_subscriptions_recommendation} r
              WHERE r.status IN (:proposed, :accepted)
                AND (
                    r.validuntil IS NULL
                    OR r.validuntil > :now
                )",
            [
                'proposed' =>
                    RecommendationStatus::PROPOSED,
                'accepted' =>
                    RecommendationStatus::ACCEPTED,
                'now' => $now,
            ]
        );
    }
}