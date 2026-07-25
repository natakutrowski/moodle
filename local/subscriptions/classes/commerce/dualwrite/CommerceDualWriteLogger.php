<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

final class CommerceDualWriteLogger {
    public function log(CommerceDualWriteResult $result, string $trigger): void {
        if ($result->is_successful() || $result->get_status() === CommerceDualWriteResult::STATUS_DISABLED) {
            return;
        }

        $payload = [
            'component' => 'local_subscriptions',
            'phase' => '7.93I5',
            'trigger' => $trigger,
            'family' => $result->get_family(),
            'legacyid' => $result->get_legacy_id(),
            'status' => $result->get_status(),
            'purchaseuuid' => $result->get_purchase_uuid(),
            'error' => $result->get_error_message(),
            'differences' => $result->get_differences(),
        ];

        debugging('Commerce dual-write: ' . json_encode($payload, JSON_UNESCAPED_SLASHES), DEBUG_DEVELOPER);
    }
}
