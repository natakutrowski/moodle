<?php

namespace local_subscriptions\commerce\fulfillment\bridge;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\postaction\CommercePostFulfillmentCoordinator;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/**
 * Optional entry point between confirmed payment and Commerce fulfillment.
 *
 * It is disabled by default and is not wired into any checkout or webhook in
 * phase 7.93E.
 */
final class CommercePostPaymentBridge {

    public function __construct(
        private readonly CommerceFulfillmentCoordinator $fulfillment,
        private readonly CommercePostFulfillmentCoordinator $postactions,
        private readonly CommerceFulfillmentFeatureToggle $toggle
    ) {
    }

    public function execute(
        CommercePurchasePreparation $preparation,
        CommerceFulfillmentContext $context
    ): CommercePostPaymentResult {
        if (!$this->toggle->is_enabled()) {
            return new CommercePostPaymentResult(false);
        }

        $operations = $this->fulfillment->plan($preparation);
        $batch = $this->fulfillment->fulfill($operations, $context);
        $postreport = $this->postactions->execute($batch, $context);

        return new CommercePostPaymentResult(
            true,
            $batch,
            $postreport
        );
    }
}
