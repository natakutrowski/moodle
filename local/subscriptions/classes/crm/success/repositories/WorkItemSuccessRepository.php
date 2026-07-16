<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemStatus;

/**
 * Reads aggregated Work Item statistics for Customer Success.
 *
 * Only Work Items explicitly targeting the Moodle user are included.
 */
final class WorkItemSuccessRepository {

    private const ITEM_TABLE =
        'local_subscriptions_work_item';

    public function is_available(): bool {
        global $DB;

        return $DB->get_manager()->table_exists(
            new \xmldb_table(self::ITEM_TABLE)
        );
    }

    /**
     * Returns normalized Work Item statistics for one user.
     *
     * @return array<string,int|float|null>
     */
    public function get_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Work Item success userid must be greater than zero.'
            );
        }

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Work Item success timestamp must be greater than zero.'
            );
        }

        if (!$this->is_available()) {
            return $this->empty_statistics();
        }

        /*
         * Moodle named SQL placeholders must be unique throughout a query.
         *
         * The active status list is used three times, so we generate three
         * independent placeholder groups:
         *
         * - all active Work Items;
         * - urgent active Work Items;
         * - overdue active Work Items.
         */
        [$activeallsql, $activeallparams] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'workactiveall'
            );

        [$activeurgentsql, $activeurgentparams] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'workactiveurgent'
            );

        [$activeduesql, $activedueparams] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'workactivedue'
            );

        $sql = "
            SELECT
                COUNT(item.id) AS itemcount,

                COALESCE(SUM(
                    CASE
                        WHEN item.status {$activeallsql}
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS activecount,

                COALESCE(SUM(
                    CASE
                        WHEN item.status = :blockedstatus
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS blockedcount,

                COALESCE(SUM(
                    CASE
                        WHEN item.priority IN (
                            :urgentpriority,
                            :criticalpriority
                        )
                         AND item.status {$activeurgentsql}
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS urgentactivecount,

                COALESCE(SUM(
                    CASE
                        WHEN item.dueat IS NOT NULL
                         AND item.dueat > 0
                         AND item.dueat < :measuredat
                         AND item.status {$activeduesql}
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS overduecount,

                COALESCE(SUM(
                    CASE
                        WHEN item.status = :resolvedstatus
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS resolvedcount,

                COALESCE(SUM(
                    CASE
                        WHEN item.status = :closedstatus
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS closedcount,

                COALESCE(SUM(
                    CASE
                        WHEN item.status = :cancelledstatus
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS cancelledcount,

                COALESCE(SUM(
                    CASE
                        WHEN item.status IN (
                            :unassignedopen,
                            :unassignedprogress,
                            :unassignedblocked,
                            :unassignedwaiting
                        )
                         AND item.assigneduserid IS NULL
                         AND item.assignedteamid IS NULL
                        THEN 1
                        ELSE 0
                    END
                ), 0) AS unassignedactivecount,

                MIN(
                    CASE
                        WHEN item.status IN (
                            :oldestopen,
                            :oldestprogress,
                            :oldestblocked,
                            :oldestwaiting
                        )
                        THEN item.timecreated
                        ELSE NULL
                    END
                ) AS oldestactiveat,

                MAX(item.timecreated) AS lastcreatedat,

                MAX(
                    CASE
                        WHEN item.status = :lastresolvedstatus
                        THEN item.resolvedat
                        ELSE 0
                    END
                ) AS lastresolvedat

              FROM {local_subscriptions_work_item} item
             WHERE item.targetuserid = :targetuserid
        ";

        $params = [
            'targetuserid' => $userid,
            'measuredat' => $measuredat,

            'blockedstatus' => WorkItemStatus::BLOCKED,

            'urgentpriority' => WorkItemPriority::URGENT,
            'criticalpriority' => WorkItemPriority::CRITICAL,

            'resolvedstatus' => WorkItemStatus::RESOLVED,
            'closedstatus' => WorkItemStatus::CLOSED,
            'cancelledstatus' => WorkItemStatus::CANCELLED,

            'unassignedopen' => WorkItemStatus::OPEN,
            'unassignedprogress' => WorkItemStatus::IN_PROGRESS,
            'unassignedblocked' => WorkItemStatus::BLOCKED,
            'unassignedwaiting' => WorkItemStatus::WAITING,

            'oldestopen' => WorkItemStatus::OPEN,
            'oldestprogress' => WorkItemStatus::IN_PROGRESS,
            'oldestblocked' => WorkItemStatus::BLOCKED,
            'oldestwaiting' => WorkItemStatus::WAITING,

            'lastresolvedstatus' => WorkItemStatus::RESOLVED,
        ];

        $params += $activeallparams;
        $params += $activeurgentparams;
        $params += $activedueparams;

        $record = $DB->get_record_sql(
            $sql,
            $params
        );

        $oldestactiveat =
            (int)($record->oldestactiveat ?? 0);

        $lastcreatedat =
            (int)($record->lastcreatedat ?? 0);

        $lastresolvedat =
            (int)($record->lastresolvedat ?? 0);

        return [
            'item_count' =>
                (int)($record->itemcount ?? 0),

            'active_count' =>
                (int)($record->activecount ?? 0),

            'blocked_count' =>
                (int)($record->blockedcount ?? 0),

            'urgent_active_count' =>
                (int)($record->urgentactivecount ?? 0),

            'overdue_count' =>
                (int)($record->overduecount ?? 0),

            'resolved_count' =>
                (int)($record->resolvedcount ?? 0),

            'closed_count' =>
                (int)($record->closedcount ?? 0),

            'cancelled_count' =>
                (int)($record->cancelledcount ?? 0),

            'unassigned_active_count' =>
                (int)($record->unassignedactivecount ?? 0),

            'oldest_active_at' =>
                $oldestactiveat,

            'oldest_active_age_days' =>
                $oldestactiveat > 0
                    ? max(
                        0,
                        (int)floor(
                            ($measuredat - $oldestactiveat) /
                            DAYSECS
                        )
                    )
                    : null,

            'last_created_at' =>
                $lastcreatedat,

            'days_since_last_created' =>
                $lastcreatedat > 0
                    ? max(
                        0,
                        (int)floor(
                            ($measuredat - $lastcreatedat) /
                            DAYSECS
                        )
                    )
                    : null,

            'last_resolved_at' =>
                $lastresolvedat,

            'days_since_last_resolved' =>
                $lastresolvedat > 0
                    ? max(
                        0,
                        (int)floor(
                            ($measuredat - $lastresolvedat) /
                            DAYSECS
                        )
                    )
                    : null,
        ];
    }

    /**
     * @return array<string,int|float|null>
     */
    private function empty_statistics(): array {
        return [
            'item_count' => 0,
            'active_count' => 0,
            'blocked_count' => 0,
            'urgent_active_count' => 0,
            'overdue_count' => 0,
            'resolved_count' => 0,
            'closed_count' => 0,
            'cancelled_count' => 0,
            'unassigned_active_count' => 0,
            'oldest_active_at' => 0,
            'oldest_active_age_days' => null,
            'last_created_at' => 0,
            'days_since_last_created' => null,
            'last_resolved_at' => 0,
            'days_since_last_resolved' => null,
        ];
    }
}