<?php

namespace local_subscriptions\commerce\payment\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Logs Commerce shadow comparisons without interrupting Legacy checkout.
 */
final class CommercePaymentShadowLogger {

    public function log(
        CommercePaymentShadowReport $report,
        string $source
    ): void {
        $payload =
            $report->to_array();

        $message =
            sprintf(
                '[Commerce payment shadow][%s] %s',
                $source,
                json_encode(
                    $payload,
                    JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                )
            );

        if ($report->is_compatible()) {
            debugging(
                $message,
                DEBUG_DEVELOPER
            );

            return;
        }

        error_log(
            $message
        );
    }
}