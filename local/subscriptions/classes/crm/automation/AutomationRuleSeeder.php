<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationRuleSeeder {

    public function __construct(
        private readonly AutomationRepository $repository = new AutomationRepository(),
        private readonly AutomationRuleProvider $provider = new AutomationRuleProvider()
    ) {
    }

    public function seed_defaults(): int {
        $created = 0;

        foreach ($this->provider->default_rules() as $rule) {
            if ($this->exists($rule->key)) {
                continue;
            }

            $this->repository->save_rule($rule);
            $created++;
        }

        return $created;
    }

    private function exists(string $rulekey): bool {
        global $DB;

        return $DB->record_exists('local_subscriptions_automation_rule', [
            'rulekey' => $rulekey,
        ]);
    }
}