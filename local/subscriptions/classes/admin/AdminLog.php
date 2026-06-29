<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class AdminLog {

    public static function log(
        string $action,
        ?int $targetuserid = null,
        ?string $objecttype = null,
        ?int $objectid = null,
        array $details = []
    ): void {
        global $DB, $USER;

        $record = (object)[
            'actorid' => (int)$USER->id,
            'targetuserid' => $targetuserid,
            'action' => $action,
            'objecttype' => $objecttype,
            'objectid' => $objectid,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ipaddress' => getremoteaddr(),
            'timecreated' => time(),
        ];

        $DB->insert_record('local_subscriptions_admin_log', $record);
    }

    public static function get_for_user(int $userid, int $limit = 20): array {
        global $DB;

        return $DB->get_records(
            'local_subscriptions_admin_log',
            ['targetuserid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        );
    }
}