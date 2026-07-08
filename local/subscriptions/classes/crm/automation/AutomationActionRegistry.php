<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationActionRegistry {

    /** @var AutomationActionInterface[] */
    private array $actions = [];

    public function register(AutomationActionInterface $action): self {
        $this->actions[$action->key()] = $action;
        return $this;
    }

    public function has(string $key): bool {
        return isset($this->actions[$key]);
    }

    public function execute(AutomationAction $action, AutomationContext $context): AutomationActionResult {
        if (!$this->has($action->key)) {
            return AutomationActionResult::failure('Unknown automation action: ' . $action->key);
        }

        return $this->actions[$action->key]->execute($action, $context);
    }
}