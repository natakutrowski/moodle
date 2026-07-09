<?php

namespace local_subscriptions\crm\intelligence\cache;

defined('MOODLE_INTERNAL') || die();

final class CrmIntelligenceRuntimeCache {

    private array $items = [];

    public function get(int $userid): mixed {
        return $this->items[$userid] ?? null;
    }

    public function set(int $userid, mixed $value): void {
        $this->items[$userid] = $value;
    }

    public function has(int $userid): bool {
        return array_key_exists($userid, $this->items);
    }

    public function clear(): void {
        $this->items = [];
    }
}