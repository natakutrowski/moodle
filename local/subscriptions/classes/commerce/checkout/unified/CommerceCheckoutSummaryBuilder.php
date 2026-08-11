<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;

/** Creates the immutable boundary between Cart and Checkout. */
final class CommerceCheckoutSummaryBuilder {
    public function __construct(private readonly CommerceCheckoutValidator $validator) {}

    public function build(CommerceCartSnapshot $cart, CommerceCheckoutContext $context, ?int $at = null): CommerceCheckoutSummary {
        return new CommerceCheckoutSummary($cart, $context, $this->validator->validate($cart, $context), $at ?? time());
    }
}
