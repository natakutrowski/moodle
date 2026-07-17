<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationBatchLimits;
use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationRunStatus;
use local_subscriptions\crm\intelligence\recommendations\operations\dto\RecommendationBatchReport;

/**
 * Repository for Recommendation Engine operational run reports.
 */
final class RecommendationRunRepository {

    private const TABLE =
        'local_subscriptions_recommendation_run';

    public function start(
        string $source,
        int $startcursor,
        int $requestedlimit
    ): int {
        global $DB;

        if (
            preg_match(
                '/^[a-z][a-z0-9_.-]{1,29}$/',
                $source
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'Invalid recommendation run source.'
            );
        }

        $now = time();

        return (int)$DB->insert_record(
            self::TABLE,
            (object)[
                'status' =>
                    RecommendationRunStatus::RUNNING,
                'source' => $source,
                'startcursor' =>
                    max(0, $startcursor),
                'endcursor' =>
                    max(0, $startcursor),
                'wrapped' => 0,
                'requestedlimit' =>
                    max(1, $requestedlimit),
                'processedcount' => 0,
                'successcount' => 0,
                'failedcount' => 0,
                'generatedcount' => 0,
                'persistedcount' => 0,
                'duplicatecount' => 0,
                'correlationcount' => 0,
                'expiredcount' => 0,
                'durationms' => 0,
                'failurejson' => null,
                'startedat' => $now,
                'finishedat' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function finish(
        RecommendationBatchReport $report,
        array $failures = []
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $report->runid,
                'status' => $report->status,
                'endcursor' =>
                    $report->endcursor,
                'wrapped' =>
                    $report->wrapped ? 1 : 0,
                'processedcount' =>
                    $report->processedcount,
                'successcount' =>
                    $report->successcount,
                'failedcount' =>
                    $report->failedcount,
                'generatedcount' =>
                    $report->generatedcount,
                'persistedcount' =>
                    $report->persistedcount,
                'duplicatecount' =>
                    $report->duplicatecount,
                'correlationcount' =>
                    $report->correlationcount,
                'expiredcount' =>
                    $report->expiredcount,
                'durationms' =>
                    $report->duration_seconds() * 1000,
                'failurejson' =>
                    $this->encode_failures(
                        $failures
                    ),
                'finishedat' =>
                    $report->finishedat,
                'timemodified' => time(),
            ]
        );
    }

    public function mark_failed(
        int $runid,
        string $reason,
        ?string $exceptionclass = null
    ): void {
        global $DB;

        $now = time();

        $record = $this->get($runid);

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $runid,
                'status' =>
                    RecommendationRunStatus::FAILED,
                'failurejson' =>
                    $this->encode_failures([
                        [
                            'reason' => $reason,
                            'exceptionclass' =>
                                $exceptionclass,
                        ],
                    ]),
                'finishedat' => $now,
                'durationms' =>
                    max(
                        0,
                        ($now - (int)$record->startedat) *
                        1000
                    ),
                'timemodified' => $now,
            ]
        );
    }

    public function mark_skipped(
        string $source,
        string $reason
    ): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record(
            self::TABLE,
            (object)[
                'status' =>
                    RecommendationRunStatus::SKIPPED,
                'source' => $source,
                'startcursor' => 0,
                'endcursor' => 0,
                'wrapped' => 0,
                'requestedlimit' => 0,
                'processedcount' => 0,
                'successcount' => 0,
                'failedcount' => 0,
                'generatedcount' => 0,
                'persistedcount' => 0,
                'duplicatecount' => 0,
                'correlationcount' => 0,
                'expiredcount' => 0,
                'durationms' => 0,
                'failurejson' =>
                    $this->encode_failures([
                        [
                            'reason' => $reason,
                        ],
                    ]),
                'startedat' => $now,
                'finishedat' => $now,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function get(int $runid): \stdClass {
        global $DB;

        return $DB->get_record(
            self::TABLE,
            [
                'id' => $runid,
            ],
            '*',
            MUST_EXIST
        );
    }

    public function latest():
        ?\stdClass {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            null,
            'startedat DESC, id DESC',
            '*',
            0,
            1
        );

        if ($records === []) {
            return null;
        }

        return reset($records) ?: null;
    }

    /**
     * @return \stdClass[]
     */
    public function recent(
        int $limit = 20
    ): array {
        global $DB;

        return array_values($DB->get_records(
            self::TABLE,
            null,
            'startedat DESC, id DESC',
            '*',
            0,
            max(1, min(200, $limit))
        ));
    }

    public function mark_abandoned_runs(
        ?int $now = null
    ): int {
        global $DB;

        $now = $now ?? time();

        $threshold =
            $now -
            (
                RecommendationBatchLimits::
                    ABANDONED_RUN_MINUTES *
                MINSECS
            );

        $records = $DB->get_records_select(
            self::TABLE,
            'status = :status
             AND startedat < :threshold',
            [
                'status' =>
                    RecommendationRunStatus::RUNNING,
                'threshold' => $threshold,
            ]
        );

        foreach ($records as $record) {
            $this->mark_failed(
                (int)$record->id,
                'abandoned_run'
            );
        }

        return count($records);
    }

    public function cleanup(
        ?int $now = null
    ): int {
        global $DB;

        $now = $now ?? time();

        $threshold =
            $now -
            (
                RecommendationBatchLimits::
                    RUN_RETENTION_DAYS *
                DAYSECS
            );

        return $DB->delete_records_select(
            self::TABLE,
            'startedat < :threshold
             AND status <> :running',
            [
                'threshold' => $threshold,
                'running' =>
                    RecommendationRunStatus::RUNNING,
            ]
        );
    }

    private function encode_failures(
        array $failures
    ): ?string {
        if ($failures === []) {
            return null;
        }

        /*
         * Avoid unbounded operational records.
         */
        $failures = array_slice(
            $failures,
            0,
            100
        );

        return json_encode(
            $failures,
            JSON_THROW_ON_ERROR |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}