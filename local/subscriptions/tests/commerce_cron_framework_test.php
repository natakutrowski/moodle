<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;

final class commerce_cron_framework_test extends advanced_testcase {

    public function test_task_result_exposes_operational_metrics(): void {
        $this->resetAfterTest();

        $result = new TaskExecutionResult('test_job');
        $result->increment('scanned', 2);
        $result->add_warning('record-1', 'Test warning');
        $result->finish();

        $this->assertSame('warning', $result->status());
        $this->assertSame(2, $result->counters()['scanned']);
        $this->assertSame(1, $result->counters()['warnings']);
        $this->assertGreaterThanOrEqual(0, $result->duration_ms());
        $this->assertGreaterThanOrEqual(0, $result->peak_memory_bytes());
        $this->assertGreaterThanOrEqual(0, $result->db_queries());
    }

    public function test_commerce_tasks_extend_shared_base_class(): void {
        foreach ([
            'cleanup_login_tokens_task.php',
            'enrol_scope_fill_task.php',
            'expire_user_enrolments_task.php',
            'followup_task.php',
            'reconcile_digital_payments_task.php',
            'repair_paid_pr_task.php',
            'send_expiry_reminders_task.php',
            'subscription_rollover_task.php',
        ] as $file) {
            $source = file_get_contents(__DIR__ . '/../classes/task/' . $file);

            $this->assertStringContainsString('extends AbstractCommerceTask', $source, $file);
            $this->assertStringContainsString('create_job', $source, $file);
            $this->assertStringNotContainsString('TaskResultRenderer::trace', $source, $file);
        }
    }

    public function test_observability_foundation_exists(): void {
        foreach ([
            'base/AbstractCommerceTask.php',
            'support/CommerceTaskRunner.php',
            'persistence/CommerceTaskRunRepository.php',
            'health/CommerceCronHealthService.php',
            'health/CommerceCronHealthSnapshot.php',
            'health/CommerceCronJobHealth.php',
        ] as $file) {
            $this->assertFileExists(__DIR__ . '/../classes/commerce/task/' . $file);
        }
    }
}
