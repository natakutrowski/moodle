<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable definition of one CRM Workspace item.
 *
 * A Workspace item may represent:
 * - a Dashboard Card;
 * - an onboarding widget;
 * - a system widget;
 * - any future reusable CRM panel.
 */
final class WorkspaceItemDefinition {

    public const TYPE_CARD = 'card';
    public const TYPE_WIDGET = 'widget';
    public const TYPE_SYSTEM = 'system';

    /**
     * @param string $key Stable item identifier.
     * @param string $label Human-readable label.
     * @param string $description Short description.
     * @param string $icon Decorative icon.
     * @param string $zone Default Workspace zone.
     * @param int $span Number of grid columns occupied.
     * @param string $type Item type.
     * @param bool $hideable Whether the user may hide the item.
     * @param bool $movable Whether the user may reorder the item.
     * @param bool $defaultvisible Whether the item is visible by default.
     * @param callable $renderer Lazy renderer returning HTML.
     * @param WorkspaceItemActionDefinition[] $actions Contextual custom actions.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly string $icon,
        public readonly string $zone,
        public readonly int $span,
        public readonly string $type,
        public readonly bool $hideable,
        public readonly bool $movable,
        public readonly bool $defaultvisible,
        private readonly mixed $renderer,
        private readonly array $actions = []
    ) {
        if ($this->key === '') {
            throw new \coding_exception(
                'A Workspace item requires a stable key.'
            );
        }

        if ($this->zone === '') {
            throw new \coding_exception(
                'A Workspace item requires a zone.'
            );
        }

        if (!is_callable($this->renderer)) {
            throw new \coding_exception(
                'A Workspace item requires a callable renderer.'
            );
        }

        foreach ($this->actions as $action) {
            if (
                !$action instanceof
                    WorkspaceItemActionDefinition
            ) {
                throw new \coding_exception(
                    'Workspace item actions must use ' .
                    WorkspaceItemActionDefinition::class . '.'
                );
            }
        }

    }

    /**
     * Lazily renders the item.
     */
    public function render(): string {
        $renderer = $this->renderer;
        $html = $renderer();

        return is_string($html) ? $html : '';
    }

    /**
     * Returns the normalized column span.
     */
    public function normalized_span(): int {
        return max(1, min(3, $this->span));
    }

    /**
     * Returns whether the item is a Card.
     */
    public function is_card(): bool {
        return $this->type === self::TYPE_CARD;
    }

    /**
     * Returns whether the item is a widget.
     */
    public function is_widget(): bool {
        return $this->type === self::TYPE_WIDGET;
    }

    /**
     * Returns the contextual custom actions.
     *
     * @return WorkspaceItemActionDefinition[]
     */
    public function actions(): array {
        return $this->actions;
    }

}