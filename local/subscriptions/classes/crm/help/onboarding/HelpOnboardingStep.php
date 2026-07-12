<?php

namespace local_subscriptions\crm\help\onboarding;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

final class HelpOnboardingStep {

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $icon,
        public readonly moodle_url $url,
        public readonly int $priority = 100
    ) {
    }
}