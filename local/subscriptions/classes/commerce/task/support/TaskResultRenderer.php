<?php

namespace local_subscriptions\commerce\task\support;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\dto\TaskExecutionResult;

/**
 * Renders one compact summary followed by actionable diagnostics.
 */
final class TaskResultRenderer {

    public static function trace(TaskExecutionResult $result): void {
        $metrics = $result->counters();
        $metrics['status'] = $result->status();
        $metrics['duration_ms'] = $result->duration_ms();
        $metrics['peak_memory_bytes'] = $result->peak_memory_bytes();
        $metrics['db_queries'] = $result->db_queries();

        $parts = [];
        foreach ($metrics as $name => $value) {
            $parts[] = $name . '=' . $value;
        }

        self::line($result, implode(' ', $parts));

        foreach ($result->warnings() as $warning) {
            self::line(
                $result,
                '[warning][' . $warning['reference'] . '] ' . $warning['message'],
            );
        }

        foreach ($result->errors() as $error) {
            self::line(
                $result,
                '[error][' . $error['reference'] . '][' . $error['exception'] . '] '
                    . $error['message'],
            );
        }
    }

    private static function line(TaskExecutionResult $result, string $message): void {
        mtrace(
            '[local_subscriptions][commerce_cron][' . $result->job_name() . '] ' . $message,
        );
    }
}
