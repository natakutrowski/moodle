<?php

namespace local_subscriptions\commerce\task\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\dto\TaskExecutionResult;

/**
 * Persists compact cron execution history for operational observability.
 */
final class CommerceTaskRunRepository {

    private const TABLE = 'local_subs_commerce_cron_run';
    private const RETENTION_SECONDS = 90 * DAYSECS;

    public function store(TaskExecutionResult $result): int {
        global $DB;

        $record = (object) [
            'jobname' => $result->job_name(),
            'status' => $result->status(),
            'countersjson' => json_encode($result->counters(), JSON_THROW_ON_ERROR),
            'warningsjson' => json_encode($result->warnings(), JSON_THROW_ON_ERROR),
            'errorsjson' => json_encode($result->errors(), JSON_THROW_ON_ERROR),
            'startedat' => $result->started_at(),
            'finishedat' => $result->finished_at(),
            'durationms' => $result->duration_ms(),
            'peakmemorybytes' => $result->peak_memory_bytes(),
            'dbqueries' => $result->db_queries(),
            'timecreated' => time(),
        ];

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    public function purge_expired(?int $now = null): int {
        global $DB;

        $threshold = ($now ?? time()) - self::RETENTION_SECONDS;
        return (int) $DB->delete_records_select(self::TABLE, 'timecreated < :threshold', [
            'threshold' => $threshold,
        ]);
    }

    public function latest_by_job(): array {
        global $DB;

        $sql = "SELECT runs.*
                  FROM {" . self::TABLE . "} runs
                  JOIN (
                        SELECT jobname, MAX(id) AS maxid
                          FROM {" . self::TABLE . "}
                      GROUP BY jobname
                  ) latest ON latest.maxid = runs.id
              ORDER BY runs.jobname";

        return $DB->get_records_sql($sql);
    }

    public function aggregate_since(int $since): array {
        global $DB;

        $sql = "SELECT jobname,
                       COUNT(1) AS executions,
                       AVG(durationms) AS averagedurationms,
                       MAX(durationms) AS maxdurationms,
                       SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failures,
                       SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) AS warnings,
                       SUM(CASE WHEN status = 'locked' THEN 1 ELSE 0 END) AS lockmisses
                  FROM {" . self::TABLE . "}
                 WHERE timecreated >= :since
              GROUP BY jobname
              ORDER BY jobname";

        return $DB->get_records_sql($sql, ['since' => $since]);
    }
}
