<?php

namespace local_subscriptions\crm\work\repositories;

defined('MOODLE_INTERNAL') || die();

final class WorkTeamRepository {

    public function all(): array {
        global $DB;

        $teams = array_values(
            $DB->get_records(
                'local_subscriptions_work_team',
                [],
                'name ASC, id ASC'
            )
        );

        if ($teams === []) {
            return [];
        }

        $teamids = array_map(
            static fn(\stdClass $team): int =>
                (int)$team->id,
            $teams
        );

        [$insql, $params] = $DB->get_in_or_equal(
            $teamids,
            SQL_PARAMS_NAMED,
            'workteam'
        );

        $members = array_values(
            $DB->get_records_sql(
                "
                    SELECT
                        member.*,
                        u.firstname,
                        u.lastname,
                        u.firstnamephonetic,
                        u.lastnamephonetic,
                        u.middlename,
                        u.alternatename
                    FROM {local_subscriptions_work_team_member} member
                    JOIN {user} u
                        ON u.id = member.userid
                    WHERE member.teamid {$insql}
                ORDER BY
                        member.teamid ASC,
                        u.lastname ASC,
                        u.firstname ASC
                ",
                $params
            )
        );

        $membersbyteam = [];

        foreach ($members as $member) {
            $membersbyteam[(int)$member->teamid][] =
                $member;
        }

        foreach ($teams as $team) {
            $team->members =
                $membersbyteam[(int)$team->id] ?? [];
        }

        return $teams;
    }

    public function members(int $teamid): array {
        global $DB;
        return array_values($DB->get_records_sql(
            "SELECT member.*, u.firstname, u.lastname,
                    u.firstnamephonetic, u.lastnamephonetic,
                    u.middlename, u.alternatename
               FROM {local_subscriptions_work_team_member} member
               JOIN {user} u ON u.id = member.userid
              WHERE member.teamid = :teamid
           ORDER BY u.lastname ASC, u.firstname ASC",
            ['teamid' => $teamid]
        ));
    }

    public function create(string $name, string $description): int {
        global $DB;
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Work team name cannot be empty.');
        }
        $now = time();
        return (int)$DB->insert_record('local_subscriptions_work_team', (object)[
            'name' => $name,
            'description' => trim($description),
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function toggle(int $teamid): void {
        global $DB;
        $team = $DB->get_record('local_subscriptions_work_team', ['id' => $teamid], '*', MUST_EXIST);
        $DB->update_record('local_subscriptions_work_team', (object)[
            'id' => $teamid,
            'enabled' => empty($team->enabled) ? 1 : 0,
            'timemodified' => time(),
        ]);
    }

    public function add_member(int $teamid, int $userid, string $role): void {
        global $DB;
        if ($userid <= 0) {
            throw new \InvalidArgumentException('Invalid team member.');
        }
        if ($DB->record_exists('local_subscriptions_work_team_member', [
            'teamid' => $teamid,
            'userid' => $userid,
        ])) {
            return;
        }
        $DB->insert_record('local_subscriptions_work_team_member', (object)[
            'teamid' => $teamid,
            'userid' => $userid,
            'role' => $role === 'lead' ? 'lead' : 'member',
            'timecreated' => time(),
        ]);
    }

    public function remove_member(int $teamid, int $userid): void {
        global $DB;
        $DB->delete_records('local_subscriptions_work_team_member', [
            'teamid' => $teamid,
            'userid' => $userid,
        ]);
    }

    public function eligible_users(): array {
        global $DB, $CFG;

        return array_values(
            $DB->get_records_select(
                'user',
                '
                    deleted = 0
                    AND suspended = 0
                    AND id <> :guestid
                ',
                [
                    'guestid' => (int)$CFG->siteguest,
                ],
                'lastname ASC, firstname ASC',
                implode(',', [
                    'id',
                    'firstname',
                    'lastname',
                    'firstnamephonetic',
                    'lastnamephonetic',
                    'middlename',
                    'alternatename',
                ])
            )
        );
    }

}