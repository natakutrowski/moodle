<?php

namespace local_subscriptions\crm\admin_tools\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\admin_tools\AdminToolStatuses;

/**
 * Database access for CRM administrative tool execution history.
 */
final class AdminToolRunRepository {

    private const TABLE =
        'local_subscriptions_admin_tool_run';

    public function create_running(
        string $toolkey,
        int $actorid,
        string $risklevel,
        string $requestid,
        array $parameters
    ): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record(
            self::TABLE,
            (object)[
                'toolkey' => $toolkey,
                'actorid' => $actorid,
                'status' =>
                    AdminToolStatuses::RUNNING,
                'risklevel' => $risklevel,
                'requestid' => $requestid,
                'parametersjson' =>
                    $this->encode($parameters),
                'resultjson' => null,
                'errormessage' => null,
                'startedat' => $now,
                'finishedat' => null,
                'durationms' => null,
                'timecreated' => $now,
            ]
        );
    }

    public function complete(
        int $runid,
        string $status,
        array $result,
        int $durationms
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $runid,
                'status' => $status,
                'resultjson' =>
                    $this->encode($result),
                'errormessage' => null,
                'finishedat' => time(),
                'durationms' =>
                    max(0, $durationms),
            ]
        );
    }

    public function fail(
        int $runid,
        string $message,
        int $durationms
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $runid,
                'status' =>
                    AdminToolStatuses::FAILED,
                'resultjson' => null,
                'errormessage' => $message,
                'finishedat' => time(),
                'durationms' =>
                    max(0, $durationms),
            ]
        );
    }

    public function recent(
        int $limit = 50
    ): array {
        global $DB;

        return array_values(
            $DB->get_records(
                self::TABLE,
                [],
                'timecreated DESC, id DESC',
                '*',
                0,
                max(
                    1,
                    min(200, $limit)
                )
            )
        );
    }

    public function last_for_tool(
        string $toolkey
    ): ?\stdClass {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            [
                'toolkey' => $toolkey,
            ],
            'timecreated DESC, id DESC',
            '*',
            0,
            1
        );

        if (empty($records)) {
            return null;
        }

        return reset($records) ?: null;
    }

    private function encode(
        array $value
    ): string {
        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_INVALID_UTF8_SUBSTITUTE
        );

        if ($encoded === false) {
            return '{}';
        }

        return $encoded;
    }
}