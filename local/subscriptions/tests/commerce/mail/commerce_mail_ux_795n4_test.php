<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;

final class commerce_mail_ux_795n4_test extends advanced_testcase {
    public function test_n41_journal_ux_contract(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/mail/index.php');
        $styles = file_get_contents($root . '/styles/commerce_mail_admin.css');
        $navigation = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailSectionNavigationRenderer.php'
        );
        $resolver = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailAdminContextResolver.php'
        );

        self::assertIsString($page);
        self::assertIsString($styles);
        self::assertIsString($navigation);
        self::assertIsString($resolver);

        foreach ([
            'commerce-mail-kpi-strip',
            'commerce-mail-filter-panel',
            'commerce_mail_columns',
            'commerce_mail_export',
            'commerce-mail-journal-table',
            'commerce-mail-row-actions-menu',
            'commerce_mail_context_order',
            'CommerceMailAdminContextResolver',
        ] as $needle) {
            self::assertStringContainsString($needle, $page);
        }

        self::assertStringContainsString('commerce_mail_nav_journal', $navigation);
        self::assertStringContainsString('commerce_mail_nav_templates', $navigation);
        self::assertStringContainsString('commerce_mail_nav_campaigns', $navigation);
        self::assertStringContainsString("'/local/subscriptions/admin/users/view.php'", $resolver);
        self::assertStringContainsString("'/local/subscriptions/admin/commerce/purchases/view.php'", $resolver);
        self::assertStringContainsString("'/local/subscriptions/admin/commerce/products/view.php'", $resolver);
        self::assertStringContainsString('.commerce-mail-kpi-strip', $styles);
    }

    public function test_n41_repository_supports_period_sort_and_statistics(): void {
        $this->resetAfterTest(true);

        $repository = new CommerceMailQueueRepository();
        $first = $repository->enqueue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('alpha@example.test', 'Alpha'),
            new CommerceMailContext(['purchase' => ['reference' => 'A']]),
            'fr',
            CommerceMailIdempotencyKey::normalise('n41:alpha')
        ));
        $second = $repository->enqueue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('zulu@example.test', 'Zulu'),
            new CommerceMailContext(['purchase' => ['reference' => 'Z']]),
            'fr',
            CommerceMailIdempotencyKey::normalise('n41:zulu')
        ));
        $repository->mark_sent((int)$first->id, 'Alpha sent', time());
        $repository->mark_failed((int)$second->id, 'Failure', time());

        $result = $repository->search([
            'datefrom' => time() - DAYSECS,
            'dateto' => time() + DAYSECS,
            'sort' => 'recipient',
            'dir' => 'asc',
        ], 0, 25);

        self::assertSame(2, $result['total']);
        self::assertSame('alpha@example.test', $result['records'][0]->recipientemail);
        self::assertSame('zulu@example.test', $result['records'][1]->recipientemail);

        $stats = $repository->statistics([
            'datefrom' => time() - DAYSECS,
            'dateto' => time() + DAYSECS,
        ]);
        self::assertSame(2, $stats['total']);
        self::assertSame(1, $stats['sent']);
        self::assertSame(1, $stats['failed']);
    }

    public function test_n41_language_catalogues_have_new_ux_strings(): void {
        $root = dirname(__DIR__, 3);
        foreach (['en', 'fr', 'ru'] as $language) {
            $catalogue = file_get_contents($root . '/lang/' . $language . '/local_subscriptions.php');
            self::assertIsString($catalogue);
            foreach ([
                'commerce_mail_nav_journal',
                'commerce_mail_nav_templates',
                'commerce_mail_nav_campaigns',
                'commerce_mail_filters_title',
                'commerce_mail_kpi_total',
                'commerce_mail_kpi_sent',
                'commerce_mail_context_column',
                'commerce_mail_export',
            ] as $key) {
                self::assertStringContainsString("\$string['{$key}']", $catalogue);
            }
        }
    }
    public function test_n42_immediate_send_and_journal_polish_contract(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/mail/index.php');
        $action = file_get_contents($root . '/admin/commerce/mail/action.php');
        $service = file_get_contents($root . '/classes/commerce/mail/admin/CommerceMailAdminService.php');
        $processor = file_get_contents($root . '/classes/commerce/mail/CommerceMailQueueProcessor.php');
        $repository = file_get_contents($root . '/classes/commerce/mail/CommerceMailQueueRepository.php');
        $styles = file_get_contents($root . '/styles/commerce_mail_admin.css');

        self::assertStringContainsString("'action' => 'sendnow'", $page);
        self::assertStringContainsString("\$action === 'sendnow'", $action);
        self::assertStringContainsString('function send_now', $service);
        self::assertStringContainsString('CommerceMailRuntime::processor()->process_ids([$id])', $service);
        self::assertStringContainsString('mark_processing_now', $processor);
        self::assertStringContainsString('function mark_processing_now', $repository);
        self::assertStringContainsString('commerce-mail-health-compact', $styles);
        self::assertStringContainsString('commerce-mail-type-badge', $styles);
        self::assertStringNotContainsString("commerce-mail-id-link']), 'commerce-mail-row-id'", $page);
        self::assertStringContainsString('commerce_mail_search_placeholder_n42', $page);
    }

    public function test_n42_force_claim_can_ignore_future_schedule_but_only_for_queued_mail(): void {
        $this->resetAfterTest(true);
        global $DB;

        $repository = new CommerceMailQueueRepository();
        $record = $repository->enqueue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('now@example.test', 'Now'),
            new CommerceMailContext(['purchase' => ['reference' => 'NOW']]),
            'fr',
            CommerceMailIdempotencyKey::normalise('n42:send-now')
        ));
        $future = time() + HOURSECS;
        $DB->set_field('local_subs_commerce_mail', 'nextruntime', $future, ['id' => (int)$record->id]);

        self::assertFalse($repository->mark_processing((int)$record->id, time()));
        self::assertTrue($repository->mark_processing_now((int)$record->id, time()));
        self::assertFalse($repository->mark_processing_now((int)$record->id, time()));
    }


    public function test_n42b_send_now_establishes_page_context_and_preserves_redirect(): void {
        $root = dirname(__DIR__, 3);
        $action = file_get_contents($root . '/admin/commerce/mail/action.php');
        $service = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailAdminService.php'
        );

        self::assertIsString($action);
        self::assertIsString($service);
        self::assertStringContainsString(
            '$PAGE->set_context(\\context_system::instance())',
            $action
        );
        self::assertStringContainsString(
            "\$PAGE->set_url(new moodle_url('/local/subscriptions/admin/commerce/mail/action.php'))",
            $action
        );
        self::assertStringContainsString('ob_get_level()', $service);
        self::assertStringContainsString('ob_start()', $service);
        self::assertStringContainsString('ob_end_clean()', $service);
    }


    public function test_n43_audit_mail_is_hidden_by_default_but_can_be_included(): void {
        $this->resetAfterTest(true);

        $repository = new CommerceMailQueueRepository();

        $normal = $repository->enqueue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('client@example.test', 'Client'),
            new CommerceMailContext(['purchase' => ['reference' => 'N43-NORMAL']]),
            'fr',
            CommerceMailIdempotencyKey::normalise('n43:normal')
        ));
        $audit = $repository->enqueue(new CommerceMailRequest(
            CommerceMailType::PURCHASE_RECEIPT,
            new CommerceMailRecipient('log@campusfr.fr', 'Audit'),
            new CommerceMailContext(['purchase' => ['reference' => 'N43-AUDIT']]),
            'fr',
            CommerceMailIdempotencyKey::normalise('n43:audit:audit')
        ));

        $repository->mark_sent((int)$normal->id, 'Normal', time());
        $repository->mark_sent((int)$audit->id, 'Audit', time());

        $hidden = $repository->search([], 0, 25);
        self::assertSame(1, $hidden['total']);
        self::assertSame('client@example.test', $hidden['records'][0]->recipientemail);

        $visible = $repository->search(['includeaudit' => true], 0, 25);
        self::assertSame(2, $visible['total']);

        $stats = $repository->statistics([]);
        self::assertSame(1, $stats['total']);
        self::assertSame(1, $stats['sent']);
    }

    public function test_n43_operational_kpis_exclude_audit_and_track_offer_queue_check(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/mail/index.php');
        $service = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailAdminService.php'
        );
        $repository = file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailQueueRepository.php'
        );
        $task = file_get_contents(
            $root . '/classes/task/process_personal_offer_mail_queue_task.php'
        );

        self::assertIsString($page);
        self::assertIsString($service);
        self::assertIsString($repository);
        self::assertIsString($task);

        self::assertStringContainsString("optional_param('includeaudit'", $page);
        self::assertStringContainsString("\$kpifilters['includeaudit'] = false", $page);
        self::assertStringContainsString('commerce_mail_kpi_offers_sent', $page);
        self::assertStringContainsString('commerce_mail_kpi_offers_pending', $page);
        self::assertStringContainsString('commerce_mail_kpi_sent_last_hour', $page);
        self::assertStringContainsString('operational_statistics', $service);
        self::assertStringContainsString('count_non_audit_sent_since', $repository);
        self::assertStringContainsString('personal_offer_operational_counts', $repository);
        self::assertStringContainsString(
            "'personal_offer_mail_last_check_at'",
            $task
        );
    }

    public function test_n43_language_catalogues_have_audit_and_operational_kpi_strings(): void {
        $root = dirname(__DIR__, 3);
        foreach (['en', 'fr', 'ru'] as $language) {
            $catalogue = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertIsString($catalogue);
            foreach ([
                'commerce_mail_include_audit',
                'commerce_mail_kpi_offers_sent',
                'commerce_mail_kpi_offers_pending',
                'commerce_mail_kpi_sent_last_hour',
                'commerce_mail_kpi_last_check',
                'commerce_mail_kpi_smtp_load_help',
            ] as $key) {
                self::assertStringContainsString("\$string['{$key}']", $catalogue);
            }
        }
    }


    public function test_n44_configuration_workspace_and_worker_controls_contract(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/mail/configuration.php'
        );
        $navigation = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailSectionNavigationRenderer.php'
        );
        $repository = file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailQueueRepository.php'
        );

        self::assertIsString($page);
        self::assertIsString($navigation);
        self::assertIsString($repository);

        foreach ([
            'commerce_mail_transactional_enabled',
            'commerce_mail_transactional_batch_size',
            'commerce_mail_transactional_hourly_limit',
            'personal_offer_mail_enabled',
            'personal_offer_mail_batch_size',
            'personal_offer_mail_hourly_limit',
            'commerce_mail_audit_enabled',
            'commerce_mail_audit_batch_size',
            'commerce_mail_audit_hourly_limit',
            'commerce_mail_global_hourly_limit',
        ] as $key) {
            self::assertStringContainsString($key, $page);
        }

        self::assertStringContainsString(
            "public const CONFIGURATION = 'configuration'",
            $navigation
        );
        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/mail/configuration.php',
            $navigation
        );
        self::assertStringContainsString(
            'count_all_sent_since',
            $repository
        );
        self::assertStringContainsString(
            'count_transactional_sent_since',
            $repository
        );
    }

    public function test_n44_workers_honour_functional_switches_and_limits(): void {
        $root = dirname(__DIR__, 3);
        $transactional = file_get_contents(
            $root . '/classes/task/process_commerce_mail_queue_task.php'
        );
        $personal = file_get_contents(
            $root . '/classes/task/process_personal_offer_mail_queue_task.php'
        );
        $audit = file_get_contents(
            $root . '/classes/task/process_commerce_mail_audit_queue_task.php'
        );

        self::assertIsString($transactional);
        self::assertIsString($personal);
        self::assertIsString($audit);

        self::assertStringContainsString(
            "'commerce_mail_transactional_enabled'",
            $transactional
        );
        self::assertStringContainsString(
            "'commerce_mail_transactional_batch_size'",
            $transactional
        );
        self::assertStringContainsString(
            "'commerce_mail_transactional_hourly_limit'",
            $transactional
        );
        self::assertStringContainsString(
            "'commerce_mail_global_hourly_limit'",
            $transactional
        );

        self::assertStringContainsString(
            "'personal_offer_mail_enabled'",
            $personal
        );
        self::assertStringContainsString(
            "'commerce_mail_global_hourly_limit'",
            $personal
        );

        self::assertStringContainsString(
            "'commerce_mail_audit_enabled'",
            $audit
        );
        self::assertStringContainsString(
            "'commerce_mail_global_hourly_limit'",
            $audit
        );
    }

    public function test_n44_configuration_strings_exist_in_all_languages(): void {
        $root = dirname(__DIR__, 3);
        $required = [
            'commerce_mail_nav_configuration',
            'commerce_mail_configuration_title',
            'commerce_mail_configuration_saved',
            'commerce_mail_configuration_global_hourly',
            'commerce_mail_configuration_transactional_title',
            'commerce_mail_configuration_personal_title',
            'commerce_mail_configuration_audit_title',
            'commerce_mail_configuration_processing_enabled',
            'commerce_mail_configuration_batch_size',
            'commerce_mail_configuration_hourly_limit',
            'commerce_mail_configuration_open_scheduled_tasks',
        ];

        foreach (['en', 'fr', 'ru'] as $language) {
            $catalogue = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertIsString($catalogue);
            foreach ($required as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $catalogue,
                    $language . ' is missing ' . $key
                );
            }
        }
    }

}
