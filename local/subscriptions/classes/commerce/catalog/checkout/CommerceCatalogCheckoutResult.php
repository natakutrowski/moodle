<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipelineResult;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;

/** Result of the guarded Native catalogue checkout boundary. */
final class CommerceCatalogCheckoutResult {
    public function __construct(
        private readonly CommerceCatalogPaymentPipelineResult $pipeline,
        private readonly CommercePaymentInitialization $initialization
    ) {
    }

    public function get_pipeline(): CommerceCatalogPaymentPipelineResult {
        return $this->pipeline;
    }

    public function get_initialization(): CommercePaymentInitialization {
        return $this->initialization;
    }

    public function was_executed(): bool {
        return $this->initialization->was_executed();
    }
}
