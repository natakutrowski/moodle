<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\shadow\CommerceCheckoutShadowService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Builds the transitional checkout facade from the Commerce runtime.
 */
final class CommerceCheckoutFactory {

    public static function create(): CommerceCheckoutService {
        $runtime = CommerceRuntimeFactory::create();

        return new CommerceCheckoutService(
            new CommerceCheckoutFeatureToggle(),
            new CommerceCheckoutEligibility(),
            new CommerceCheckoutShadowService(
                $runtime->legacy_payment_requests()
            ),
            $runtime->legacy_payment_requests(),
            $runtime->payment_contexts(),
            $runtime->payment_orchestration()
        );
    }
}
