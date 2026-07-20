<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use local_subscriptions\crm\admin_tools\tools\AutomationRunTool;
use local_subscriptions\crm\admin_tools\tools\DigitalPaymentReconciliationTool;
use local_subscriptions\crm\admin_tools\tools\HelpValidationTool;
use local_subscriptions\crm\admin_tools\tools\InboxDiagnosticsTool;
use local_subscriptions\crm\admin_tools\tools\InboxSyncTool;
use local_subscriptions\crm\admin_tools\tools\IntelligenceSnapshotTool;
use local_subscriptions\crm\admin_tools\tools\RecommendationRunTool;

/**
 * Central registry of CRM administrative tools.
 */
final class AdminToolRegistry {

    /**
     * @return AdminTool[]
     */
    public function all(): array {
        return [
            new InboxSyncTool(),
            new InboxDiagnosticsTool(),
            new AutomationRunTool(),
            new IntelligenceSnapshotTool(),
            new RecommendationRunTool(),
            new DigitalPaymentReconciliationTool(),
            new HelpValidationTool(),
        ];
    }

    /**
     * @return AdminTool[]
     */
    public function visible(
        ?context $context = null
    ): array {
        $context =
            $context ??
            context_system::instance();

        return array_values(
            array_filter(
                $this->all(),
                static function (
                    AdminTool $tool
                ) use ($context): bool {
                    return has_capability(
                        $tool->required_capability(),
                        $context
                    );
                }
            )
        );
    }

    public function find(
        string $key
    ): ?AdminTool {
        foreach ($this->all() as $tool) {
            if ($tool->key() === $key) {
                return $tool;
            }
        }

        return null;
    }

    public function require(
        string $key
    ): AdminTool {
        $tool = $this->find($key);

        if (!$tool) {
            throw new \moodle_exception(
                'crm_admin_tool_unknown',
                'local_subscriptions'
            );
        }

        return $tool;
    }
}