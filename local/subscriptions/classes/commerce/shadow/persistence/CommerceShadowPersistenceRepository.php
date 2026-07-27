<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceLegacyFulfillmentObservation;
use local_subscriptions\commerce\shadow\CommerceShadowComparison;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionReport;

/** Persists one immutable Shadow comparison run. */
interface CommerceShadowPersistenceRepository {
    public function save(
        string $entrypoint,
        CommerceLegacyFulfillmentObservation $legacy,
        CommerceShadowExecutionReport $native,
        CommerceShadowComparison $comparison,
        string $classification,
        ?string $errorclass = null,
        ?string $errormessage = null
    ): int;
}
