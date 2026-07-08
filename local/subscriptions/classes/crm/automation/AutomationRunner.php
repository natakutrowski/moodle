<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationRunner {

    public function __construct(
        private readonly AutomationRepository $rules,
        private readonly AutomationEngine $engine
    ) {
    }

    public function run_for_context(AutomationContext $context): array {
        $rules = $this->rules->get_enabled_rules_for_trigger($context->triggerkey);

        if (empty($rules)) {
            return [];
        }

        return $this->engine->run($rules, $context);
    }
}