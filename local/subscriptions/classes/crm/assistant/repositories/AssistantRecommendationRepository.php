<?php

namespace local_subscriptions\crm\assistant\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\dto\AssistantOverview;
use local_subscriptions\crm\assistant\dto\AssistantRecommendation;
use local_subscriptions\crm\assistant\dto\AssistantRecommendationCriteria;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;

/**
 * Read-only repository for the CRM Assistant workspace.
 *
 * All Assistant SQL remains contained in this class.
 */
final class AssistantRecommendationRepository {

    private const TABLE =
        'local_subscriptions_recommendation';

    public function get_overview(): AssistantOverview {
        global $DB;

        $now = time();

        $sql = "SELECT
                    SUM(
                        CASE
                            WHEN r.status IN (:proposed, :accepted)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowactive
                             )
                            THEN 1 ELSE 0
                        END
                    ) AS activecount,

                    SUM(
                        CASE
                            WHEN r.prioritylevel = :critical
                             AND r.status IN (:proposedcritical, :acceptedcritical)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowcritical
                             )
                            THEN 1 ELSE 0
                        END
                    ) AS criticalcount,

                    SUM(
                        CASE
                            WHEN r.prioritylevel = :urgent
                             AND r.status IN (:proposedurgent, :acceptedurgent)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowurgent
                             )
                            THEN 1 ELSE 0
                        END
                    ) AS urgentcount,

                    SUM(
                        CASE
                            WHEN r.status = :acceptedonly
                            THEN 1 ELSE 0
                        END
                    ) AS acceptedcount,

                    SUM(
                        CASE
                            WHEN r.recommendationtype = :crossdomain
                             AND r.status IN (:proposedcross, :acceptedcross)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowcross
                             )
                            THEN 1 ELSE 0
                        END
                    ) AS crossdomaincount,

                    COUNT(
                        DISTINCT CASE
                            WHEN r.targettype = :usertarget
                             AND r.targetid IS NOT NULL
                             AND r.status IN (:proposeduser, :accepteduser)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowuser
                             )
                            THEN r.targetid
                            ELSE NULL
                        END
                    ) AS usercount
                FROM {" . self::TABLE . "} r";

        $record = $DB->get_record_sql(
            $sql,
            [
                'proposed' =>
                    RecommendationStatus::PROPOSED,
                'accepted' =>
                    RecommendationStatus::ACCEPTED,
                'nowactive' => $now,

                'critical' => 'critical',
                'proposedcritical' =>
                    RecommendationStatus::PROPOSED,
                'acceptedcritical' =>
                    RecommendationStatus::ACCEPTED,
                'nowcritical' => $now,

                'urgent' => 'urgent',
                'proposedurgent' =>
                    RecommendationStatus::PROPOSED,
                'acceptedurgent' =>
                    RecommendationStatus::ACCEPTED,
                'nowurgent' => $now,

                'acceptedonly' =>
                    RecommendationStatus::ACCEPTED,

                'crossdomain' =>
                    RecommendationType::CROSS_DOMAIN_INTERVENTION,
                'proposedcross' =>
                    RecommendationStatus::PROPOSED,
                'acceptedcross' =>
                    RecommendationStatus::ACCEPTED,
                'nowcross' => $now,

                'usertarget' => 'user',
                'proposeduser' =>
                    RecommendationStatus::PROPOSED,
                'accepteduser' =>
                    RecommendationStatus::ACCEPTED,
                'nowuser' => $now,
            ]
        );

        return new AssistantOverview(
            active:
                (int)($record->activecount ?? 0),
            critical:
                (int)($record->criticalcount ?? 0),
            urgent:
                (int)($record->urgentcount ?? 0),
            accepted:
                (int)($record->acceptedcount ?? 0),
            crossdomain:
                (int)($record->crossdomaincount ?? 0),
            users:
                (int)($record->usercount ?? 0)
        );
    }

    /**
     * @return AssistantRecommendation[]
     */
    public function search(
        AssistantRecommendationCriteria $criteria
    ): array {
        global $DB;

        [$where, $params] =
            $this->build_conditions($criteria);

        $sql = "SELECT
                    r.*,
                    u.firstname,
                    u.lastname,
                    u.firstnamephonetic,
                    u.lastnamephonetic,
                    u.middlename,
                    u.alternatename,
                    u.email
                FROM {" . self::TABLE . "} r
           LEFT JOIN {user} u
                  ON r.targettype = :joinedtargettype
                 AND u.id = r.targetid
                {$where}
            ORDER BY
                    CASE r.prioritylevel
                        WHEN 'critical' THEN 5
                        WHEN 'urgent' THEN 4
                        WHEN 'high' THEN 3
                        WHEN 'normal' THEN 2
                        ELSE 1
                    END DESC,
                    r.priority DESC,
                    r.lastdetectedat DESC,
                    r.id DESC";

        $params['joinedtargettype'] = 'user';

        $records = $DB->get_records_sql(
            $sql,
            $params,
            $criteria->offset,
            $criteria->limit
        );

        return array_values(array_map(
            fn(\stdClass $record):
                AssistantRecommendation =>
                    $this->map_record($record),
            $records
        ));
    }

    public function count(
        AssistantRecommendationCriteria $criteria
    ): int {
        global $DB;

        [$where, $params] =
            $this->build_conditions($criteria);

        return (int)$DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {" . self::TABLE . "} r
              {$where}",
            $params
        );
    }

    /**
     * @return AssistantRecommendation[]
     */
    public function get_for_user(
        int $userid,
        int $limit = 10
    ): array {
        return $this->search(
            new AssistantRecommendationCriteria(
                userid: $userid,
                limit: max(1, min(50, $limit))
            )
        );
    }

    public function get(
        int $recommendationid
    ): AssistantRecommendation {
        global $DB;

        $record = $DB->get_record_sql(
            "SELECT
                    r.*,
                    u.firstname,
                    u.lastname,
                    u.firstnamephonetic,
                    u.lastnamephonetic,
                    u.middlename,
                    u.alternatename,
                    u.email
               FROM {" . self::TABLE . "} r
          LEFT JOIN {user} u
                 ON r.targettype = :targettype
                AND u.id = r.targetid
              WHERE r.id = :recommendationid",
            [
                'targettype' => 'user',
                'recommendationid' =>
                    $recommendationid,
            ],
            MUST_EXIST
        );

        return $this->map_record($record);
    }

    /**
     * @return array{0:string,1:array}
     */
    private function build_conditions(
        AssistantRecommendationCriteria $criteria
    ): array {
        global $DB;

        $conditions = [];
        $params = [];

        if ($criteria->active_only()) {
            [$statussql, $statusparams] =
                $DB->get_in_or_equal(
                    RecommendationStatus::active(),
                    SQL_PARAMS_NAMED,
                    'assistantstatus'
                );

            $conditions[] =
                "r.status {$statussql}";

            $conditions[] =
                "(r.validuntil IS NULL
                  OR r.validuntil > :assistantnow)";

            $params = array_merge(
                $params,
                $statusparams
            );

            $params['assistantnow'] = time();
        }

        if ($criteria->status !== null) {
            $conditions[] =
                'r.status = :filterstatus';

            $params['filterstatus'] =
                $criteria->status;
        }

        if ($criteria->type !== null) {
            $conditions[] =
                'r.recommendationtype = :filtertype';

            $params['filtertype'] =
                $criteria->type;
        }

        if (
            $criteria->prioritylevel !== null
        ) {
            $conditions[] =
                'r.prioritylevel = :filterpriority';

            $params['filterpriority'] =
                $criteria->prioritylevel;
        }

        if ($criteria->userid !== null) {
            $conditions[] =
                'r.targettype = :filtertargettype';

            $conditions[] =
                'r.targetid = :filtertargetid';

            $params['filtertargettype'] =
                'user';

            $params['filtertargetid'] =
                $criteria->userid;
        }

        $where = $conditions !== []
            ? 'WHERE ' .
                implode(' AND ', $conditions)
            : '';

        return [$where, $params];
    }

    private function map_record(
        \stdClass $record
    ): AssistantRecommendation {
        $targetname = null;

        if (
            !empty($record->firstname) ||
            !empty($record->lastname)
        ) {
            $targetname = fullname($record);
        } else if (!empty($record->email)) {
            $targetname = (string)$record->email;
        }

        return new AssistantRecommendation(
            id: (int)$record->id,
            fingerprint:
                (string)$record->fingerprint,
            key:
                (string)$record->recommendationkey,
            type:
                (string)$record->recommendationtype,
            presentationtype:
                (string)$record->presentationtype,
            priority:
                (int)$record->priority,
            prioritylevel:
                (string)$record->prioritylevel,
            status:
                (string)$record->status,
            targettype:
                $record->targettype !== null
                    ? (string)$record->targettype
                    : null,
            targetid:
                $record->targetid !== null
                    ? (int)$record->targetid
                    : null,
            targetname: $targetname,
            sources:
                $this->decode_json(
                    $record->sourcesjson ?? null
                ),
            evidence:
                $this->decode_json(
                    $record->evidencejson ?? null
                ),
            actions:
                $this->decode_json(
                    $record->actionsjson ?? null
                ),
            generatedat:
                (int)$record->generatedat,
            validuntil:
                $record->validuntil !== null
                    ? (int)$record->validuntil
                    : null,
            firstdetectedat:
                (int)$record->firstdetectedat,
            lastdetectedat:
                (int)$record->lastdetectedat,
            dismissalreason:
                $record->dismissalreason !== null
                    ? (string)$record->dismissalreason
                    : null
        );
    }

    private function decode_json(
        ?string $json
    ): array {
        if (
            $json === null ||
            trim($json) === ''
        ) {
            return [];
        }

        $decoded = json_decode(
            $json,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }
}