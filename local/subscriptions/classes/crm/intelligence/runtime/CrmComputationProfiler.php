<?php

namespace local_subscriptions\crm\intelligence\runtime;

defined('MOODLE_INTERNAL') || die();

/**
 * Lightweight profiler for CRM Intelligence computations.
 *
 * Profiling is only emitted when Moodle developer debugging is enabled.
 */
final class CrmComputationProfiler {

    /**
     * Starts a profiling span.
     *
     * @return array
     */
    public static function start(): array {
        global $DB;

        return [
            'microtime' => microtime(true),
            'queries' => $DB->perf_get_reads() +
                $DB->perf_get_writes(),
            'memory' => memory_get_usage(true),
        ];
    }

    /**
     * Finishes and logs a profiling span.
     *
     * @param string $runid Computation run identifier.
     * @param int $userid Moodle user ID.
     * @param string $stage Computation stage.
     * @param array $start Start values returned by start().
     */
    public static function finish(
        string $runid,
        int $userid,
        string $stage,
        array $start
    ): void {
        global $CFG, $DB;

        if (
            empty($CFG->debug) ||
            $CFG->debug < DEBUG_DEVELOPER
        ) {
            return;
        }

        $durationms = max(
            0,
            (int)round(
                (microtime(true) -
                    (float)$start['microtime']) *
                1000
            )
        );

        $queries =
            (
                $DB->perf_get_reads() +
                $DB->perf_get_writes()
            ) -
            (int)$start['queries'];

        $memorydelta =
            memory_get_usage(true) -
            (int)$start['memory'];

        error_log(
            sprintf(
                '[CRM Compute] run=%s userid=%d stage=%s duration_ms=%d queries=%d memory_delta_bytes=%d',
                $runid,
                $userid,
                $stage,
                $durationms,
                max(0, $queries),
                $memorydelta
            )
        );
    }

    private function __construct() {
    }
}