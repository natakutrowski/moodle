<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa\returnflow;

defined('MOODLE_INTERNAL') || die();

final class NativeAlfaInstantReturnSleeper implements AlfaInstantReturnSleeperInterface {
    public function sleep_microseconds(int $microseconds): void {
        if ($microseconds > 0) {
            usleep($microseconds);
        }
    }
}
