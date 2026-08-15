<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\legacy\CommerceLegacyAutomaticMailPolicy;

final class commerce_mail_legacy_automatic_cleanup_n57_test extends advanced_testcase {
    public function test_legacy_automatic_mail_policy_is_fail_closed_by_default(): void {
        $this->resetAfterTest(true);

        unset_config(
            CommerceLegacyAutomaticMailPolicy::MASTER,
            'local_subscriptions'
        );
        unset_config(
            CommerceLegacyAutomaticMailPolicy::PAYMENT_REMINDERS,
            'local_subscriptions'
        );
        unset_config(
            CommerceLegacyAutomaticMailPolicy::EXPIRY_REMINDERS,
            'local_subscriptions'
        );
        unset_config(
            CommerceLegacyAutomaticMailPolicy::LIFECYCLE,
            'local_subscriptions'
        );

        self::assertFalse(CommerceLegacyAutomaticMailPolicy::master_enabled());
        self::assertFalse(
            CommerceLegacyAutomaticMailPolicy::payment_reminders_enabled()
        );
        self::assertFalse(
            CommerceLegacyAutomaticMailPolicy::expiry_reminders_enabled()
        );
        self::assertFalse(
            CommerceLegacyAutomaticMailPolicy::lifecycle_emails_enabled()
        );
    }

    public function test_individual_legacy_channels_require_master_switch(): void {
        $this->resetAfterTest(true);

        set_config(
            CommerceLegacyAutomaticMailPolicy::PAYMENT_REMINDERS,
            1,
            'local_subscriptions'
        );
        set_config(
            CommerceLegacyAutomaticMailPolicy::EXPIRY_REMINDERS,
            1,
            'local_subscriptions'
        );
        set_config(
            CommerceLegacyAutomaticMailPolicy::LIFECYCLE,
            1,
            'local_subscriptions'
        );

        self::assertFalse(
            CommerceLegacyAutomaticMailPolicy::payment_reminders_enabled()
        );
        self::assertFalse(
            CommerceLegacyAutomaticMailPolicy::expiry_reminders_enabled()
        );
        self::assertFalse(
            CommerceLegacyAutomaticMailPolicy::lifecycle_emails_enabled()
        );

        set_config(
            CommerceLegacyAutomaticMailPolicy::MASTER,
            1,
            'local_subscriptions'
        );

        self::assertTrue(
            CommerceLegacyAutomaticMailPolicy::payment_reminders_enabled()
        );
        self::assertTrue(
            CommerceLegacyAutomaticMailPolicy::expiry_reminders_enabled()
        );
        self::assertTrue(
            CommerceLegacyAutomaticMailPolicy::lifecycle_emails_enabled()
        );
    }

    public function test_payment_followup_keeps_expiration_but_gates_old_reminder_mail(): void {
        $root = dirname(__DIR__, 3);
        $job = file_get_contents(
            $root . '/classes/commerce/task/job/PaymentFollowupJob.php'
        );

        self::assertStringContainsString(
            'find_pending_to_expire',
            $job
        );
        self::assertStringContainsString(
            'mark_expired_if_pending',
            $job
        );
        self::assertStringContainsString(
            'CommerceLegacyAutomaticMailPolicy::payment_reminders_enabled()',
            $job
        );

        $gate = strpos(
            $job,
            'CommerceLegacyAutomaticMailPolicy::payment_reminders_enabled()'
        );
        $expiration = strpos($job, 'find_pending_to_expire');
        $reminders = strpos($job, 'find_reminder_candidates');

        self::assertNotFalse($gate);
        self::assertNotFalse($expiration);
        self::assertNotFalse($reminders);
        self::assertLessThan($gate, $expiration);
        self::assertLessThan($reminders, $gate);
    }

    public function test_expiry_and_lifecycle_legacy_notifications_are_gated(): void {
        $root = dirname(__DIR__, 3);
        $expiry = file_get_contents(
            $root . '/classes/commerce/task/job/SubscriptionExpiryReminderJob.php'
        );
        $lifecycle = file_get_contents(
            $root . '/classes/service/SubscriptionLifecycleService.php'
        );

        self::assertStringContainsString(
            'CommerceLegacyAutomaticMailPolicy::expiry_reminders_enabled()',
            $expiry
        );
        self::assertStringContainsString(
            'CommerceLegacyAutomaticMailPolicy::lifecycle_emails_enabled()',
            $lifecycle
        );

        // Access state transitions are intentionally preserved.
        self::assertStringContainsString(
            'subscription_manager::enrol_user_to_courses',
            $lifecycle
        );
        self::assertStringContainsString(
            'subscription_manager::suspend_user_in_plan_courses',
            $lifecycle
        );
    }

    public function test_historical_trial_reminders_have_no_scheduled_caller(): void {
        $root = dirname(__DIR__, 3);
        $tasksource = file_get_contents($root . '/db/tasks.php');

        foreach ([
            'T_TRIAL_REM3',
            'T_TRIAL_PRE_SUSPEND',
            'T_TRIAL_SUSPENDED',
            'T_TRIAL_EXPIRED',
        ] as $type) {
            self::assertStringNotContainsString($type, $tasksource);
        }

        $automaticroots = [
            $root . '/classes/task',
            $root . '/classes/commerce/task/job',
            $root . '/classes/service',
        ];
        foreach ($automaticroots as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS
                )
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $source = file_get_contents($file->getPathname());
                foreach ([
                    'T_TRIAL_REM3',
                    'T_TRIAL_PRE_SUSPEND',
                    'T_TRIAL_SUSPENDED',
                    'T_TRIAL_EXPIRED',
                ] as $type) {
                    self::assertStringNotContainsString(
                        'mailer::' . $type,
                        $source,
                        $file->getPathname()
                    );
                }
            }
        }
    }

    public function test_crm_automation_cron_does_not_send_customer_email(): void {
        $root = dirname(__DIR__, 3);
        $runner = file_get_contents(
            $root . '/classes/crm/automation/AutomationCronRunner.php'
        );
        $rules = file_get_contents(
            $root . '/classes/crm/automation/AutomationRuleProvider.php'
        );

        foreach ([$runner, $rules] as $source) {
            self::assertStringNotContainsString('mailer::dispatch', $source);
            self::assertStringNotContainsString('email_to_user', $source);
            self::assertStringNotContainsString('CommerceMailRuntime', $source);
        }
    }

    public function test_mail_configuration_exposes_legacy_safety_zone(): void {
        $root = dirname(__DIR__, 3);
        $configuration = file_get_contents(
            $root . '/admin/commerce/mail/configuration.php'
        );

        foreach ([
            'legacy_auto_mail_enabled',
            'legacy_auto_payment_reminders_enabled',
            'legacy_auto_expiry_reminders_enabled',
            'legacy_auto_lifecycle_emails_enabled',
            'legacyfollowup',
            'legacyexpiry',
            'legacylifecycle',
        ] as $token) {
            self::assertStringContainsString($token, $configuration);
        }
    }

    public function test_n54_user_fixture_no_longer_forces_uninstalled_language(): void {
        $root = dirname(__DIR__, 3);
        $n54 = file_get_contents(
            $root . '/tests/commerce/mail/commerce_mail_marketing_campaign_n54_test.php'
        );

        $methodstart = strpos(
            $n54,
            'test_generic_marketing_campaign_freezes_template_and_audience'
        );
        $methodend = strpos(
            $n54,
            'public function test_scheduled_campaign',
            $methodstart
        );
        $method = substr($n54, $methodstart, $methodend - $methodstart);

        self::assertStringNotContainsString("'lang' => 'fr'", $method);
    }
}
