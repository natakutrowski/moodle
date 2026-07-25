<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

/** Small static integration boundary for transitional Legacy code. */
final class CommerceDualWriteBridge {
    public static function subscription(int $subscriptionid, string $trigger): CommerceDualWriteResult {
        return CommerceDualWriteFactory::create()->synchronise('subscription', $subscriptionid, $trigger);
    }

    public static function digital(int $paymentrequestid, string $trigger): CommerceDualWriteResult {
        return CommerceDualWriteFactory::create()->synchronise('digital', $paymentrequestid, $trigger);
    }
}
