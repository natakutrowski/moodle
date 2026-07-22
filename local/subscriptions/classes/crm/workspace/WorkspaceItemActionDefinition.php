<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable definition of one contextual Workspace item action.
 *
 * An action is rendered by the generic Workspace item menu, while its
 * business behaviour is handled by the module that owns the item.
 */
final class WorkspaceItemActionDefinition {

    public const STYLE_DEFAULT = 'default';
    public const STYLE_DANGER = 'danger';

    /**
     * @param string $key Stable action identifier.
     * @param string $label Human-readable action label.
     * @param string $icon Decorative icon.
     * @param string $style Visual style.
     * @param bool $enabled Whether the action is currently available.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $icon = '',
        public readonly string $style = self::STYLE_DEFAULT,
        public readonly bool $enabled = true
    ) {
        if ($this->key === '') {
            throw new \coding_exception(
                'A Workspace item action requires a stable key.'
            );
        }

        if ($this->label === '') {
            throw new \coding_exception(
                'A Workspace item action requires a label.'
            );
        }

        if (
            !in_array(
                $this->style,
                [
                    self::STYLE_DEFAULT,
                    self::STYLE_DANGER,
                ],
                true
            )
        ) {
            throw new \coding_exception(
                'Invalid Workspace item action style.'
            );
        }
    }

    /**
     * Returns whether the action uses the danger visual style.
     */
    public function is_danger(): bool {
        return $this->style === self::STYLE_DANGER;
    }
}