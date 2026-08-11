<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Customer-facing state for the unified post-payment page. */
final class CommercePostPaymentState {
    public function __construct(
        public readonly string $code,
        public readonly string $tone,
        public readonly bool $canretry,
        public readonly bool $showaccesses
    ) {
    }
}
