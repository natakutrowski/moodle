<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\digital\digital_payment_reconciler;

/**
 * Reconciles pending digital payment requests with providers.
 */
final class DigitalPaymentReconciliationTool
    implements AdminTool {

    public function key(): string {
        return
            AdminToolKeys::
                DIGITAL_RECONCILIATION;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_digital_reconciliation',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_digital_reconciliation_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '¤';
    }

    public function required_capability(): string {
        return Capabilities::MANAGE_DIGITAL;
    }

    public function risk_level(): string {
        return AdminToolRiskLevels::HIGH;
    }

    public function requires_confirmation(): bool {
        return true;
    }

    public function lock_key(): string {
        return
            'local_subscriptions_' .
            AdminToolKeys::
                DIGITAL_RECONCILIATION;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        $limit = max(
            1,
            min(
                100,
                (int)(
                    $context->parameters['limit']
                    ?? 10
                )
            )
        );

        $result =
            digital_payment_reconciler::
                reconcile_pending([
                    'limit' => $limit,
                    'minage' => 300,
                    'maxage' => 2 * DAYSECS,
                ]);

        if ((int)$result['errors'] > 0) {
            return AdminToolExecutionResult::failed(
                get_string(
                    'crm_admin_tool_digital_reconciliation_partial',
                    'local_subscriptions'
                ),
                $result
            );
        }

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_digital_reconciliation_success',
                'local_subscriptions'
            ),
            $result
        );
    }
}