<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\command\observability;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\command\dto\CommerceCommandRequest;
use local_subscriptions\commerce\command\dto\CommerceCommandResult;
final class CommerceWriteObserver {
    public function observe(CommerceCommandRequest $request, CommerceCommandResult $result, int $durationms): void {
        if ($result->is_successful() || $result->get_status() === 'disabled' || $result->get_status() === 'skipped') { return; }
        debugging('[I10D write] ' . json_encode([
            'consumer' => $request->get_consumer(), 'family' => $request->get_family(), 'legacyid' => $request->get_legacy_id(),
            'trigger' => $request->get_trigger(), 'status' => $result->get_status(), 'durationms' => $durationms,
            'error' => $result->get_error_message(),
        ], JSON_UNESCAPED_SLASHES), DEBUG_DEVELOPER);
    }
}
