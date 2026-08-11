<?php

namespace local_subscriptions\crm\success\runtime;

defined('MOODLE_INTERNAL') || die();

/**
 * Measures internal Customer Success repository operations.
 *
 * The profiler is intentionally passive:
 * - it does not alter returned values;
 * - it does not catch repository exceptions;
 * - it does not perform database writes;
 * - it only emits logs in developer debugging mode.
 */
final class CustomerSuccessRepositoryProfiler {

    /**
     * Executes and profiles one repository operation.
     *
     * @template T
     *
     * @param string $repository Stable repository identifier.
     * @param int $userid Moodle user identifier.
     * @param string $step Stable internal step identifier.
     * @param callable(): T $operation Operation to execute.
     * @return T
     */
    public static function measure(
        string $repository,
        int $userid,
        string $step,
        callable $operation
    ): mixed {
        if (!self::is_enabled()) {
            return $operation();
        }

        global $DB;

        $started = microtime(true);
        $queriesbefore = $DB->perf_get_queries();
        $memorybefore = memory_get_usage(true);

        try {
            return $operation();
        } finally {
            $durationms = max(
                0,
                (int)round(
                    (microtime(true) - $started) * 1000
                )
            );

            $queries = max(
                0,
                $DB->perf_get_queries() - $queriesbefore
            );

            $memorydelta =
                memory_get_usage(true) - $memorybefore;

            $message = sprintf(
                '[CRM Repository] repository=%s userid=%d step=%s duration_ms=%d queries=%d memory_delta_bytes=%d',
                self::normalize_identifier($repository),
                $userid,
                self::normalize_identifier($step),
                $durationms,
                $queries,
                $memorydelta
            );

            if (
                defined('CLI_SCRIPT') &&
                CLI_SCRIPT
            ) {
                mtrace($message);
            } else {
                error_log($message);
            }
        }
    }

    /**
     * Whether detailed repository profiling is active.
     */
    private static function is_enabled(): bool {
        global $CFG;

        // PHPUnit treats mtrace/error_log profiler output as unexpected output.
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return false;
        }

        return isset($CFG->debug)
            && (int)$CFG->debug >= DEBUG_DEVELOPER;
    }

    /**
     * Keeps log identifiers stable and grep-friendly.
     */
    private static function normalize_identifier(
        string $identifier
    ): string {
        $identifier = strtolower(
            trim($identifier)
        );

        $identifier = preg_replace(
            '/[^a-z0-9_]+/',
            '_',
            $identifier
        );

        return trim(
            (string)$identifier,
            '_'
        );
    }

    private function __construct() {
    }
}