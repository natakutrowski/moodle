<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

/** Logs only runtime read anomalies and fallbacks. */
final class CommerceRuntimeReadLogger {
    public function log(CommerceRuntimeReadResult $result, string $trigger): void {
        if (!$result->has_issue()) { return; }
        debugging('[Commerce runtime read] ' . json_encode([
            'family' => $result->get_family(),
            'legacyid' => $result->get_legacy_id(),
            'mode' => $result->get_mode(),
            'status' => $result->get_status(),
            'source' => $result->get_source(),
            'fallback' => $result->used_fallback(),
            'trigger' => $trigger,
            'message' => $result->get_message(),
            'differences' => $result->get_differences(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), DEBUG_DEVELOPER);
    }
}
