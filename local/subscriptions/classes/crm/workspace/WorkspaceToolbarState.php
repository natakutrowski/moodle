<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable initial state of a CRM Workspace toolbar.
 *
 * Runtime states such as editing, dirty or saving are subsequently
 * managed by the generic JavaScript edit-mode engine.
 */
final class WorkspaceToolbarState {

    /**
     * @param string $workspacekey Stable Workspace identifier.
     * @param int $hiddencount Number of initially hidden items.
     * @param bool $canreset Whether the default layout may be restored.
     * @param bool $cansave Whether the layout may be saved.
     */
    public function __construct(
        public readonly string $workspacekey,
        public readonly int $hiddencount,
        public readonly bool $canreset = true,
        public readonly bool $cansave = true
    ) {
        if ($this->workspacekey === '') {
            throw new \coding_exception(
                'A Workspace toolbar requires a Workspace key.'
            );
        }
    }

    /**
     * Returns a normalized hidden-item count.
     */
    public function normalized_hidden_count(): int {
        return max(0, $this->hiddencount);
    }
}