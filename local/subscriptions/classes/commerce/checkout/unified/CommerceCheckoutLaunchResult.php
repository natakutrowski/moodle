<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;

/** Unified H1 result containing the frozen transaction and provider initialization. */
final class CommerceCheckoutLaunchResult {
    public function __construct(
        private readonly CommerceCheckoutSnapshot $snapshot,
        private readonly CommercePaymentInitialization $initialization
    ) {}

    public function get_snapshot(): CommerceCheckoutSnapshot { return $this->snapshot; }
    public function get_initialization(): CommercePaymentInitialization { return $this->initialization; }
}
