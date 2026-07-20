<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract implemented by every CRM administrative tool.
 */
interface AdminTool {

    /**
     * Stable technical identifier.
     */
    public function key(): string;

    /**
     * Translated title.
     */
    public function title(): string;

    /**
     * Translated description.
     */
    public function description(): string;

    /**
     * Decorative icon.
     */
    public function icon(): string;

    /**
     * Additional business capability required by this tool.
     */
    public function required_capability(): string;

    /**
     * Low, normal or high.
     */
    public function risk_level(): string;

    /**
     * Whether the action controller must show confirmation.
     */
    public function requires_confirmation(): bool;

    /**
     * Stable lock name.
     */
    public function lock_key(): string;

    /**
     * Executes the operation.
     */
    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult;
}