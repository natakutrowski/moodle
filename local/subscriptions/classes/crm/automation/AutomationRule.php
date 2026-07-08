<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationRule {

    /**
     * @param AutomationCondition[] $conditions
     * @param AutomationAction[] $actions
     */
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $name,
        public readonly AutomationTrigger $trigger,
        public readonly array $conditions,
        public readonly array $actions,
        public readonly bool $enabled = true,
        public readonly int $priority = 100,
        public readonly array $metadata = []
    ) {
    }

    public function has_actions(): bool {
        return !empty($this->actions);
    }

    public function is_enabled(): bool {
        return $this->enabled;
    }

    public function matches_trigger(string $triggerkey): bool {
        return $this->trigger->key === $triggerkey;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'trigger' => $this->trigger->to_array(),
            'conditions' => array_map(static fn(AutomationCondition $condition): array => $condition->to_array(), $this->conditions),
            'actions' => array_map(static fn(AutomationAction $action): array => $action->to_array(), $this->actions),
            'enabled' => $this->enabled,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ];
    }
}