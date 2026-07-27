<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\runtime\CommerceDualWriteShadowObserver;

/** Small static integration boundary for transitional Legacy code. */
final class CommerceDualWriteBridge {
    public static function subscription(int $subscriptionid, string $trigger): CommerceDualWriteResult {
        $result = CommerceDualWriteFactory::create()->synchronise('subscription', $subscriptionid, $trigger);
        CommerceDualWriteShadowObserver::after_synchronise($result, $trigger);
        return $result;
    }

    public static function digital(int $paymentrequestid, string $trigger): CommerceDualWriteResult {
        $result = CommerceDualWriteFactory::create()->synchronise('digital', $paymentrequestid, $trigger);
        CommerceDualWriteShadowObserver::after_synchronise($result, $trigger);
        return $result;
    }
}
