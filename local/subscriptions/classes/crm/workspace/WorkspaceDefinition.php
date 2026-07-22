<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Describes one reusable CRM Workspace.
 */
final class WorkspaceDefinition {

    /** @var array<string, WorkspaceItemDefinition> */
    private array $items = [];

    /** @var string[] */
    private array $zones = [];

    /**
     * @param string $key Stable Workspace identifier.
     * @param string $preferencekey Moodle user preference key.
     * @param string[] $zones Ordered list of allowed zones.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $preferencekey,
        array $zones
    ) {
        if ($this->key === '') {
            throw new \coding_exception(
                'A Workspace requires a stable key.'
            );
        }

        if ($this->preferencekey === '') {
            throw new \coding_exception(
                'A Workspace requires a preference key.'
            );
        }

        foreach ($zones as $zone) {
            if (
                !is_string($zone)
                || $zone === ''
                || in_array($zone, $this->zones, true)
            ) {
                continue;
            }

            $this->zones[] = $zone;
        }

        if ($this->zones === []) {
            throw new \coding_exception(
                'A Workspace requires at least one zone.'
            );
        }
    }

    /**
     * Registers an item.
     */
    public function register(
        WorkspaceItemDefinition $item
    ): self {
        if (!in_array($item->zone, $this->zones, true)) {
            throw new \coding_exception(
                'The Workspace item "' .
                $item->key .
                '" uses an unknown zone: ' .
                $item->zone
            );
        }

        if (isset($this->items[$item->key])) {
            throw new \coding_exception(
                'Duplicate Workspace item key: ' .
                $item->key
            );
        }

        $this->items[$item->key] = $item;

        return $this;
    }

    /**
     * Returns all registered items.
     *
     * @return array<string, WorkspaceItemDefinition>
     */
    public function items(): array {
        return $this->items;
    }

    /**
     * Returns one registered item.
     */
    public function item(
        string $key
    ): ?WorkspaceItemDefinition {
        return $this->items[$key] ?? null;
    }

    /**
     * Returns whether an item exists.
     */
    public function has_item(string $key): bool {
        return isset($this->items[$key]);
    }

    /**
     * Returns the ordered Workspace zones.
     *
     * @return string[]
     */
    public function zones(): array {
        return $this->zones;
    }

    /**
     * Returns the default item ordering.
     *
     * @return array<string, string[]>
     */
    public function default_order(): array {
        $order = [];

        foreach ($this->zones as $zone) {
            $order[$zone] = [];
        }

        foreach ($this->items as $key => $item) {
            $order[$item->zone][] = $key;
        }

        return $order;
    }

    /**
     * Returns the items hidden by default.
     *
     * @return string[]
     */
    public function default_hidden(): array {
        $hidden = [];

        foreach ($this->items as $key => $item) {
            if (
                !$item->defaultvisible
                && $item->hideable
            ) {
                $hidden[] = $key;
            }
        }

        return $hidden;
    }
}