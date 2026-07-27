<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_cron_remaining_tasks_test extends advanced_testcase {
    public function test_remaining_commerce_tasks_are_thin_adapters(): void {
        $expected = [
            'followup_task.php' => 'PaymentFollowupJob',
            'cleanup_login_tokens_task.php' => 'LoginTokenCleanupJob',
        ];

        foreach ($expected as $file => $job) {
            $source = file_get_contents(__DIR__ . '/../../../classes/task/' . $file);
            $this->assertStringContainsString($job, $source, $file);
            $this->assertStringContainsString('extends AbstractCommerceTask', $source, $file);
            $this->assertStringNotContainsString('global $DB', $source, $file);
            $this->assertStringNotContainsString('get_records', $source, $file);
            $this->assertStringNotContainsString('update_record', $source, $file);
            $this->assertStringNotContainsString('mailer::dispatch', $source, $file);
        }
    }

    public function test_jobs_and_repositories_exist(): void {
        foreach ([
            'classes/commerce/task/job/PaymentFollowupJob.php',
            'classes/commerce/task/job/LoginTokenCleanupJob.php',
            'classes/commerce/task/repository/PaymentFollowupRepository.php',
            'classes/commerce/task/repository/LoginTokenCleanupRepository.php',
        ] as $file) {
            $this->assertFileExists(__DIR__ . '/../../../' . $file);
        }
    }
}
