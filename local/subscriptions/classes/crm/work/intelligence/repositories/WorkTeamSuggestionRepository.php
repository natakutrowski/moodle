<?php

namespace local_subscriptions\crm\work\intelligence\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemStatus;

/**
 * Loads enabled Work Teams with workload information.
 */
final class WorkTeamSuggestionRepository {

    public function get_enabled_teams_with_workload():
        array {
        global $DB;

        [$statussql, $statusparams] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'teamload_status'
            );

        $sql = "SELECT
                    team.id,
                    team.name,
                    team.description,
                    team.enabled,
                    COUNT(
                        DISTINCT member.id
                    ) AS membercount,
                    COUNT(
                        DISTINCT CASE
                            WHEN item.status {$statussql}
                            THEN item.id
                            ELSE NULL
                        END
                    ) AS activeworkload
                FROM {local_subscriptions_work_team} team
           LEFT JOIN {local_subscriptions_work_team_member} member
                  ON member.teamid = team.id
           LEFT JOIN {local_subscriptions_work_item} item
                  ON item.assignedteamid = team.id
               WHERE team.enabled = :enabled
            GROUP BY
                    team.id,
                    team.name,
                    team.description,
                    team.enabled
            ORDER BY team.name ASC,
                    team.id ASC";

        $statusparams['enabled'] = 1;

        return array_values(
            $DB->get_records_sql(
                $sql,
                $statusparams
            )
        );
    }
}