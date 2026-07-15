<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxTeamRepository {

    private const TEAM_TABLE =
        'local_subscriptions_inbox_team';

    private const MEMBER_TABLE =
        'local_subscriptions_inbox_team_member';

    public function find(int $id): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TEAM_TABLE,
            ['id' => $id]
        );

        return $record ?: null;
    }

    public function create(
        string $name,
        ?string $description = null
    ): int {
        global $DB;

        $name = trim(
            clean_param($name, PARAM_TEXT)
        );

        if ($name === '') {
            throw new \invalid_parameter_exception(
                'Inbox team name is required.'
            );
        }

        $existing = $DB->get_record(
            self::TEAM_TABLE,
            ['name' => $name]
        );

        if ($existing) {
            return (int)$existing->id;
        }

        $now = time();

        return (int)$DB->insert_record(
            self::TEAM_TABLE,
            (object)[
                'name' => $name,
                'description' => $description,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function add_member(
        int $teamid,
        int $userid
    ): void {
        global $DB;

        if (
            $DB->record_exists(
                self::MEMBER_TABLE,
                [
                    'teamid' => $teamid,
                    'userid' => $userid,
                ]
            )
        ) {
            return;
        }

        $DB->insert_record(
            self::MEMBER_TABLE,
            (object)[
                'teamid' => $teamid,
                'userid' => $userid,
                'timecreated' => time(),
            ]
        );
    }

    public function remove_member(
        int $teamid,
        int $userid
    ): void {
        global $DB;

        $DB->delete_records(
            self::MEMBER_TABLE,
            [
                'teamid' => $teamid,
                'userid' => $userid,
            ]
        );
    }

    public function is_member(
        int $teamid,
        int $userid
    ): bool {
        global $DB;

        return $DB->record_exists(
            self::MEMBER_TABLE,
            [
                'teamid' => $teamid,
                'userid' => $userid,
            ]
        );
    }

    public function get_enabled(): array {
        global $DB;

        return array_values(
            $DB->get_records(
                self::TEAM_TABLE,
                ['enabled' => 1],
                'name ASC'
            )
        );
    }

    public function get_members(
        int $teamid
    ): array {
        global $DB;

        $sql = "
            SELECT u.id,
                   u.firstname,
                   u.lastname,
                   u.email
              FROM {" . self::MEMBER_TABLE . "} tm
              JOIN {user} u
                ON u.id = tm.userid
             WHERE tm.teamid = :teamid
               AND u.deleted = 0
          ORDER BY u.lastname ASC,
                   u.firstname ASC,
                   u.id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                ['teamid' => $teamid]
            )
        );
    }
}