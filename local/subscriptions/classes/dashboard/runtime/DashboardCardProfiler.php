<?php

namespace local_subscriptions\dashboard\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\dashboard\ui\DashboardCardUi;

/**
 * Measures and protects the runtime execution of a Dashboard Card.
 *
 * The profiler:
 * - isolates Card rendering failures;
 * - returns a safe common error state;
 * - never exposes technical exception details in the UI;
 * - does not execute database queries itself;
 * - records metrics only in developer debugging mode.
 */
final class DashboardCardProfiler {

    /**
     * Renders a Dashboard Card and optionally records runtime metrics.
     *
     * @param string $cardname Stable technical name of the Card.
     * @param callable $renderer Card rendering callback.
     * @return string Rendered HTML.
     */
    public static function render(
        string $cardname,
        callable $renderer
    ): string {
        $profilingenabled =
            self::is_enabled();

        $startedat = microtime(true);
        $querystart = null;
        $memorystart =
            memory_get_usage(true);

        if ($profilingenabled) {
            global $DB;

            $querystart =
                self::get_query_count($DB);
        }

        try {
            return (string)$renderer();
        } catch (\Throwable $exception) {
            self::log_rendering_failure(
                $cardname,
                $exception
            );

            return DashboardCardUi::shell(
                content:
                    DashboardCardUi::
                        error_state(
                            title: get_string(
                                'dashboard_state_error_title',
                                'local_subscriptions'
                            ),
                            description: get_string(
                                'dashboard_state_error_description',
                                'local_subscriptions'
                            )
                        ),
                extraclasses:
                    'crm-dashboard-runtime-error'
            );
        } finally {
            if ($profilingenabled) {
                self::log_metrics(
                    $cardname,
                    $startedat,
                    $querystart,
                    $memorystart
                );
            }
        }
    }

    /**
     * Records a Card rendering failure without exposing it in the UI.
     */
    private static function log_rendering_failure(
        string $cardname,
        \Throwable $exception
    ): void {
        $message = sprintf(
            '[CRM Dashboard] card=%s rendering_error=%s',
            self::normalize_identifier(
                $cardname
            ),
            str_replace(
                [
                    "\r",
                    "\n",
                ],
                ' ',
                $exception->getMessage()
            )
        );

        if (
            defined('CLI_SCRIPT') &&
            CLI_SCRIPT
        ) {
            mtrace($message);
            return;
        }

        error_log($message);

        debugging(
            $message,
            DEBUG_DEVELOPER
        );
    }

    /**
     * Whether detailed Dashboard profiling is enabled.
     */
    private static function is_enabled(): bool {
        global $CFG;

        return isset($CFG->debug)
            && (int)$CFG->debug >= DEBUG_DEVELOPER;
    }

    /**
     * Returns the current database query counter when available.
     *
     * @param object $database Moodle database instance.
     * @return int|null
     */
    private static function get_query_count(
        object $database
    ): ?int {
        if (!method_exists($database, 'perf_get_queries')) {
            return null;
        }

        return (int)$database->perf_get_queries();
    }

    /**
     * Writes the Card metrics to the appropriate runtime output.
     *
     * @param string $cardname Card technical name.
     * @param float $startedat Start timestamp.
     * @param int|null $querystart Query counter at start.
     * @param int $memorystart Allocated memory at start.
     */
    private static function log_metrics(
        string $cardname,
        float $startedat,
        ?int $querystart,
        int $memorystart
    ): void {
        global $DB;

        $durationms = max(
            0,
            (int)round(
                (microtime(true) - $startedat) * 1000
            )
        );

        $memorydelta = max(
            0,
            memory_get_usage(true) - $memorystart
        );

        $queryend = self::get_query_count($DB);
        $querycount = null;

        if (
            $querystart !== null
            && $queryend !== null
        ) {
            $querycount = max(
                0,
                $queryend - $querystart
            );
        }

        $querylabel = $querycount === null
            ? 'unavailable'
            : (string)$querycount;

        $message = sprintf(
            '[CRM Dashboard] card=%s duration_ms=%d queries=%s memory_delta_bytes=%d',
            self::normalize_identifier($cardname),
            $durationms,
            $querylabel,
            $memorydelta
        );

        if (
            defined('CLI_SCRIPT')
            && CLI_SCRIPT
        ) {
            mtrace($message);
        } else {
            error_log($message);
        }
    }

    /**
     * Keeps Card identifiers stable and grep-friendly.
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