<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceLegacyFulfillmentObservation;

/** Captures the observable Legacy outcome after the authoritative runtime completed. */
interface CommerceLegacyObservationCollector {
    public function collect(string $purchasereference, string $source): CommerceLegacyFulfillmentObservation;
}
