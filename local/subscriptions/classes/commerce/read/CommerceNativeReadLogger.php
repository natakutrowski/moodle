<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

/** Minimal logger for shadow-read inconsistencies. */
final class CommerceNativeReadLogger {
    public function log(CommerceNativeReadResult $result, string $trigger): void {
        if (!$result->has_issue()) {
            return;
        }
        $payload = [
            'family' => $result->get_family(),
            'legacyid' => $result->get_legacy_id(),
            'status' => $result->get_status(),
            'trigger' => $trigger,
            'message' => $result->get_message(),
            'differences' => $result->get_differences(),
        ];
        debugging('[Commerce native read shadow] ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), DEBUG_DEVELOPER);
    }
}
