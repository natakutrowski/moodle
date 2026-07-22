<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalized CRM Workspace layout.
 */
final class WorkspaceLayout {

    public const VERSION = 2;

    /**
     * @param string[] $hidden
     * @param array<string, string[]> $order
     */
    public function __construct(
        public readonly array $hidden,
        public readonly array $order
    ) {
    }

    /**
     * Returns the serializable layout representation.
     */
    public function to_array(): array {
        return [
            'version' => self::VERSION,
            'hidden' => array_values($this->hidden),
            'order' => $this->order,
        ];
    }

    /**
     * Returns whether an item is hidden.
     */
    public function is_hidden(string $key): bool {
        return in_array($key, $this->hidden, true);
    }

    /**
     * Returns the ordered visible items for one zone.
     *
     * @return string[]
     */
    public function visible_keys(string $zone): array {
        $hidden = array_fill_keys(
            $this->hidden,
            true
        );

        return array_values(
            array_filter(
                $this->order[$zone] ?? [],
                static fn(string $key): bool =>
                    !isset($hidden[$key])
            )
        );
    }

    /**
     * Returns the number of hidden items.
     */
    public function hidden_count(): int {
        return count($this->hidden);
    }
}