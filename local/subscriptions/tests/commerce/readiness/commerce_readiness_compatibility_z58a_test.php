<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_readiness_compatibility_z58a_test extends \advanced_testcase {
    public function test_compatibility_cli_uses_current_readiness_constructor_and_skips_f8d(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/readiness/audit_commerce_production_readiness.php'
        );

        $this->assertStringContainsString('new CommerceProductionReadinessAuditor(', $source);
        $this->assertStringContainsString('$DB,', $source);
        $this->assertStringContainsString("'include_backup_rollback' => false", $source);
        $this->assertStringContainsString('SKIPPED (use prod_ready.php)', $source);
    }

    public function test_deployment_checklist_uses_current_readiness_contract(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/deployment/CommerceDeploymentChecklistAuditor.php'
        );

        $this->assertStringContainsString('new CommerceProductionReadinessAuditor(', $source);
        $this->assertStringContainsString("'include_backup_rollback' => false", $source);
        $this->assertStringNotContainsString(
            'new CommerceProductionReadinessAuditor())->audit()',
            $source
        );
    }

    public function test_f8e_auditor_keeps_backup_phase_enabled_by_default(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/readiness/CommerceProductionReadinessAuditor.php'
        );

        $this->assertStringContainsString("'include_backup_rollback'", $source);
        $this->assertStringContainsString(
            "!array_key_exists('include_backup_rollback', \$options)",
            $source
        );
    }
}
