<?php

namespace local_subscriptions\crm\help\guides;

defined('MOODLE_INTERNAL') || die();

final class HelpGuide {

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $icon,
        public readonly array $steps,
        public readonly array $contexts = [],
        public readonly int $priority = 100
    ) {
    }

    public function matches_context(string $context): bool {
        return in_array($context, $this->contexts, true);
    }

    public function get_step(string $stepid): ?HelpGuideStep {
        foreach ($this->steps as $step) {
            if ($step->id === $stepid) {
                return $step;
            }
        }

        return null;
    }
}