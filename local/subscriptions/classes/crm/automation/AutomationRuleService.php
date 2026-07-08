<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationRuleService {

    public function __construct(
        private readonly AutomationRepository $repository = new AutomationRepository()
    ) {
    }

    public function get_all(): array {
        return $this->repository->get_all_rules();
    }

    public function enable(int $id): void {
        $this->repository->enable_rule($id);
    }

    public function disable(int $id): void {
        $this->repository->disable_rule($id);
    }

    public function seed_defaults(): int {
        return (new AutomationRuleSeeder())->seed_defaults();
    }
}