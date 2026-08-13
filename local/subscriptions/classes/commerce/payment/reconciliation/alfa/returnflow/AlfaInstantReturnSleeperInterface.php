<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa\returnflow;

defined('MOODLE_INTERNAL') || die();

interface AlfaInstantReturnSleeperInterface {
    public function sleep_microseconds(int $microseconds): void;
}
