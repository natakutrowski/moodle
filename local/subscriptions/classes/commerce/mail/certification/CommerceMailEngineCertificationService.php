<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailAuditCopyPolicy;
use local_subscriptions\commerce\mail\CommerceMailCustomerContentPolicy;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\service\CommerceTransactionalPurchaseMailService;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use moodle_database;

/** Read-only health and configuration audit for the Commerce transactional mail engine. */
final class CommerceMailEngineCertificationService {

    private const OUTBOX_TABLE = 'local_subs_commerce_mail';
    private const TEMPLATE_TABLE = 'local_subs_commerce_mail_tpl';
    private const TASK_CLASS = '\\local_subscriptions\\task\\process_commerce_mail_queue_task';
    private const LANGUAGES = ['fr', 'en', 'ru'];
    private const EDITORIAL_FIELDS = ['subject', 'preheader', 'heading', 'introhtml', 'outrohtml', 'signaturehtml'];

    public function __construct(private readonly moodle_database $db) {
    }

    public function certify(?int $now = null): CommerceMailCertificationReport {
        $now ??= time();
        $report = new CommerceMailCertificationReport();

        $this->check_schema($report);
        $this->check_runtime_templates($report);
        $this->check_default_editorial_matrix($report);
        $this->check_custom_templates($report);
        $this->check_queue_health($report, $now);
        $this->check_scheduled_task($report);
        $this->check_delivery_contract($report);
        $this->check_audit_copy($report);
        $this->check_required_components($report);

        return $report;
    }

    private function check_schema(CommerceMailCertificationReport $report): void {
        $manager = $this->db->get_manager();
        foreach ([self::OUTBOX_TABLE, self::TEMPLATE_TABLE] as $table) {
            $exists = $manager->table_exists($table);
            $report->add(new CommerceMailCertificationFinding(
                'schema.' . $table,
                $exists ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
                'Database table ' . $table,
                $exists ? 'Available.' : 'Missing. Run the Moodle upgrade before certification.'
            ));
        }
    }

    private function check_runtime_templates(CommerceMailCertificationReport $report): void {
        try {
            $registry = CommerceMailRuntime::template_registry();
            $missing = array_values(array_filter(
                CommerceMailType::all(),
                static fn(string $type): bool => !$registry->has($type)
            ));
            $report->add(new CommerceMailCertificationFinding(
                'runtime.templates',
                $missing === [] ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
                'Runtime template registry',
                $missing === []
                    ? count($registry->all()) . ' transactional types registered.'
                    : 'Missing types: ' . implode(', ', $missing)
            ));
        } catch (\Throwable $exception) {
            $report->add(new CommerceMailCertificationFinding(
                'runtime.templates',
                CommerceMailCertificationFinding::ERROR,
                'Runtime template registry',
                $exception->getMessage()
            ));
        }
    }

    private function check_default_editorial_matrix(CommerceMailCertificationReport $report): void {
        $invalid = [];
        foreach (self::LANGUAGES as $language) {
            foreach (CommerceMailType::all() as $type) {
                try {
                    $template = CommerceMailTemplateDefaults::get($type, $language);
                    foreach (self::EDITORIAL_FIELDS as $field) {
                        if (trim((string)($template[$field] ?? '')) === '') {
                            $invalid[] = $type . ':' . $language . ':' . $field;
                        }
                    }
                } catch (\Throwable $exception) {
                    $invalid[] = $type . ':' . $language . ':' . $exception->getMessage();
                }
            }
        }

        $report->add(new CommerceMailCertificationFinding(
            'templates.defaults',
            $invalid === [] ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
            'Default editorial template matrix',
            $invalid === [] ? (count(CommerceMailType::all()) * count(self::LANGUAGES)) . ' complete templates available (' . count(CommerceMailType::all()) . ' types × ' . count(self::LANGUAGES) . ' languages).' : implode('; ', $invalid)
        ));
    }

    private function check_custom_templates(CommerceMailCertificationReport $report): void {
        if (!$this->db->get_manager()->table_exists(self::TEMPLATE_TABLE)) {
            return;
        }

        $records = $this->db->get_records(self::TEMPLATE_TABLE, ['enabled' => 1]);
        $invalid = [];
        foreach ($records as $record) {
            if (!in_array((string)$record->mailtype, CommerceMailType::all(), true)) {
                $invalid[] = '#' . $record->id . ' unsupported type';
                continue;
            }
            if (!in_array((string)$record->language, self::LANGUAGES, true)) {
                $invalid[] = '#' . $record->id . ' unsupported language';
            }
            if (trim((string)$record->subject) === '') {
                $invalid[] = '#' . $record->id . ' empty subject';
            }
        }

        $report->add(new CommerceMailCertificationFinding(
            'templates.custom',
            $invalid === [] ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
            'Active customised templates',
            $invalid === [] ? count($records) . ' active custom template(s) valid.' : implode('; ', $invalid)
        ));
    }

    private function check_queue_health(CommerceMailCertificationReport $report, int $now): void {
        if (!$this->db->get_manager()->table_exists(self::OUTBOX_TABLE)) {
            return;
        }

        $counts = [];
        foreach (CommerceMailStatus::all() as $status) {
            $counts[$status] = $this->db->count_records(self::OUTBOX_TABLE, ['status' => $status]);
        }
        $failed = $counts[CommerceMailStatus::FAILED];
        $stale = $this->db->count_records_select(
            self::OUTBOX_TABLE,
            'status = :status AND timeprocessing IS NOT NULL AND timeprocessing < :threshold',
            ['status' => CommerceMailStatus::PROCESSING, 'threshold' => $now - 1800]
        );
        $overdue = $this->db->count_records_select(
            self::OUTBOX_TABLE,
            'status = :status AND nextruntime < :threshold',
            ['status' => CommerceMailStatus::QUEUED, 'threshold' => $now - 900]
        );

        $severity = CommerceMailCertificationFinding::OK;
        if ($stale > 0) {
            $severity = CommerceMailCertificationFinding::ERROR;
        } else if ($failed > 0 || $overdue > 0) {
            $severity = CommerceMailCertificationFinding::WARNING;
        }

        $detail = sprintf(
            'queued=%d, processing=%d, sent=%d, failed=%d, cancelled=%d, stale=%d, overdue=%d.',
            $counts[CommerceMailStatus::QUEUED],
            $counts[CommerceMailStatus::PROCESSING],
            $counts[CommerceMailStatus::SENT],
            $failed,
            $counts[CommerceMailStatus::CANCELLED],
            $stale,
            $overdue
        );
        $report->add(new CommerceMailCertificationFinding(
            'queue.health',
            $severity,
            'Persistent outbox health',
            $detail
        ));
    }

    private function check_scheduled_task(CommerceMailCertificationReport $report): void {
        $task = $this->db->get_record('task_scheduled', ['classname' => self::TASK_CLASS]);
        if (!$task) {
            $report->add(new CommerceMailCertificationFinding(
                'task.queue',
                CommerceMailCertificationFinding::ERROR,
                'Queue scheduled task',
                'Task not registered. Purge caches or run the Moodle upgrade.'
            ));
            return;
        }

        $disabled = !empty($task->disabled);
        $report->add(new CommerceMailCertificationFinding(
            'task.queue',
            $disabled ? CommerceMailCertificationFinding::ERROR : CommerceMailCertificationFinding::OK,
            'Queue scheduled task',
            $disabled ? 'Registered but disabled.' : 'Registered and enabled.'
        ));
    }

    private function check_delivery_contract(CommerceMailCertificationReport $report): void {
        $methods = [
            'deliver_payment_confirmed_purchase',
            'deliver_fulfilled_access',
        ];
        $missing = array_values(array_filter(
            $methods,
            static fn(string $method): bool => !method_exists(CommerceTransactionalPurchaseMailService::class, $method)
        ));
        $report->add(new CommerceMailCertificationFinding(
            'delivery.events',
            $missing === [] ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
            'Immediate delivery event contract',
            $missing === []
                ? 'Payment receipt and fulfilled access entry points are available.'
                : 'Missing methods: ' . implode(', ', $missing)
        ));
    }

    private function check_audit_copy(CommerceMailCertificationReport $report): void {
        $enabled = (bool)get_config('local_subscriptions', 'commerce_mail_audit_copy_enabled');
        if (!$enabled) {
            $report->add(new CommerceMailCertificationFinding(
                'audit.copy',
                CommerceMailCertificationFinding::OK,
                'Independent audit copy',
                'Disabled by configuration.'
            ));
            return;
        }

        $policy = new CommerceMailAuditCopyPolicy();
        $address = $policy->get_address();
        $types = $policy->get_types();
        $valid = validate_email($address) && $types !== [];
        $report->add(new CommerceMailCertificationFinding(
            'audit.copy',
            $valid ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
            'Independent audit copy',
            $valid
                ? $address . ' · ' . implode(', ', $types) . ($policy->include_attachment() ? ' · invoice attached' : ' · no invoice')
                : 'Enabled but the address or selected types are invalid.'
        ));
    }

    private function check_required_components(CommerceMailCertificationReport $report): void {
        $classes = [
            CommerceMailCustomerContentPolicy::class,
            '\\local_subscriptions\\commerce\\order\\invoice\\CommerceInvoicePdfService',
            '\\local_subscriptions\\commerce\\mail\\template\\studio\\CommerceMailTokenResolver',
            '\\local_subscriptions\\commerce\\mail\\template\\studio\\CommerceMailHeaderImageService',
        ];
        $missing = array_values(array_filter(
            $classes,
            static fn(string $class): bool => !class_exists($class)
        ));
        $report->add(new CommerceMailCertificationFinding(
            'components.required',
            $missing === [] ? CommerceMailCertificationFinding::OK : CommerceMailCertificationFinding::ERROR,
            'Required mail engine components',
            $missing === [] ? count($classes) . ' critical components available.' : 'Missing: ' . implode(', ', $missing)
        ));
    }
}
