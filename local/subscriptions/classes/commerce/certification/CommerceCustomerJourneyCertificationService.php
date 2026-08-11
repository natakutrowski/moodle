<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\certification\CommerceMyCoursesCertificationFinding;
use local_subscriptions\commerce\course\certification\CommerceMyCoursesCertificationService;
use local_subscriptions\commerce\customer\certification\CommerceCustomerCrmCertificationService;
use local_subscriptions\commerce\mail\certification\CommerceMailCertificationFinding;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\commerce\professional\certification\CommerceCustomerExperienceCertificationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/**
 * Read-only final certification of the complete Commerce customer journey.
 *
 * This service composes the certified I6-I9 subsystems and adds cross-cutting
 * checks for payment states, product families, Guest, customer pages and data health.
 */
final class CommerceCustomerJourneyCertificationService {
    public function __construct(private readonly \moodle_database $db) {}

    public function certify(?int $now = null): CommerceCustomerJourneyCertificationReport {
        global $CFG;
        $now ??= time();
        $report = new CommerceCustomerJourneyCertificationReport();

        $this->append_mail_certification($report, $now);
        $this->append_courses_certification($report);
        $this->append_crm_certification($report);
        $this->append_professional_certification($report);
        $this->check_payment_state_contract($report, $CFG->dirroot);
        $this->check_product_and_guest_contract($report, $CFG->dirroot);
        $this->check_customer_pages($report, $CFG->dirroot);
        $this->check_runtime_data_health($report, $now);
        $this->check_test_matrix($report, $CFG->dirroot);
        $this->check_plugin_versions($report, $CFG->dirroot);

        return $report;
    }

    private function append_mail_certification(
        CommerceCustomerJourneyCertificationReport $report,
        int $now
    ): void {
        $mailreport = (new CommerceMailEngineCertificationService($this->db))->certify($now);
        foreach ($mailreport->get_findings() as $finding) {
            $severity = match ($finding->get_severity()) {
                CommerceMailCertificationFinding::ERROR => CommerceCustomerJourneyCertificationFinding::ERROR,
                CommerceMailCertificationFinding::WARNING => CommerceCustomerJourneyCertificationFinding::WARNING,
                default => CommerceCustomerJourneyCertificationFinding::OK,
            };
            $report->add(new CommerceCustomerJourneyCertificationFinding(
                'mail.' . $finding->get_code(),
                $severity,
                'Emails — ' . $finding->get_label(),
                $finding->get_detail()
            ));
        }
    }

    private function append_courses_certification(CommerceCustomerJourneyCertificationReport $report): void {
        $coursereport = (new CommerceMyCoursesCertificationService($this->db))->certify();
        foreach ($coursereport->get_findings() as $finding) {
            $severity = match ($finding->get_severity()) {
                CommerceMyCoursesCertificationFinding::ERROR => CommerceCustomerJourneyCertificationFinding::ERROR,
                CommerceMyCoursesCertificationFinding::WARNING => CommerceCustomerJourneyCertificationFinding::WARNING,
                default => CommerceCustomerJourneyCertificationFinding::OK,
            };
            $report->add(new CommerceCustomerJourneyCertificationFinding(
                'courses.' . sha1($finding->get_label()),
                $severity,
                'Mes Cours — ' . $finding->get_label(),
                $finding->get_detail()
            ));
        }
    }

    private function append_crm_certification(CommerceCustomerJourneyCertificationReport $report): void {
        $crmreport = (new CommerceCustomerCrmCertificationService($this->db))->certify();
        foreach ($crmreport->findings as $finding) {
            $severity = match ($finding['status']) {
                'ERROR' => CommerceCustomerJourneyCertificationFinding::ERROR,
                'WARN' => CommerceCustomerJourneyCertificationFinding::WARNING,
                default => CommerceCustomerJourneyCertificationFinding::OK,
            };
            $report->add(new CommerceCustomerJourneyCertificationFinding(
                'crm.' . sha1($finding['label']),
                $severity,
                'CRM — ' . $finding['label'],
                $finding['detail']
            ));
        }
    }

    private function append_professional_certification(CommerceCustomerJourneyCertificationReport $report): void {
        $professional = (new CommerceCustomerExperienceCertificationService())->certify();
        foreach ($professional['checks'] as $check) {
            $report->add(new CommerceCustomerJourneyCertificationFinding(
                'professional.' . sha1($check['label']),
                $check['ok']
                    ? CommerceCustomerJourneyCertificationFinding::OK
                    : CommerceCustomerJourneyCertificationFinding::ERROR,
                'Expérience client — ' . $check['label'],
                $check['detail']
            ));
        }
    }

    private function check_payment_state_contract(
        CommerceCustomerJourneyCertificationReport $report,
        string $dirroot
    ): void {
        $paths = [
            $dirroot . '/local/subscriptions/order_result.php',
            $dirroot . '/local/subscriptions/classes/commerce/order/result/CommerceOrderResultState.php',
            $dirroot . '/local/subscriptions/payment_success.php',
            $dirroot . '/local/subscriptions/payment_cancel.php',
            $dirroot . '/local/subscriptions/payment_error.php',
        ];
        $source = $this->read_sources($paths);
        $states = ['success', 'processing', 'pending', 'failed', 'cancelled'];
        $missing = array_values(array_filter(
            $states,
            static fn(string $state): bool => !str_contains($source, $state)
        ));
        $retry = str_contains($source, 'retry') || str_contains($source, 'Retry');
        $ok = $missing === [] && $retry;
        $detail = $ok
            ? 'Success, processing, pending, failure, cancellation and retry are represented.'
            : 'Missing states: ' . implode(', ', $missing) . ($retry ? '' : '; retry contract missing');
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'journey.payment_states',
            $ok ? CommerceCustomerJourneyCertificationFinding::OK : CommerceCustomerJourneyCertificationFinding::ERROR,
            'Payment return states',
            $detail
        ));
    }

    private function check_product_and_guest_contract(
        CommerceCustomerJourneyCertificationReport $report,
        string $dirroot
    ): void {
        $required = [
            'course' => [
                '/local/subscriptions/classes/commerce/fulfillment/native/course/CommerceCourseAccessFulfillmentHandler.php',
                '/local/subscriptions/tests/commerce/fulfillment/commerce_native_course_fulfillment_test.php',
            ],
            'digital' => [
                '/local/subscriptions/classes/commerce/fulfillment/native/digital/CommerceDigitalDownloadFulfillmentHandler.php',
                '/local/subscriptions/tests/commerce/fulfillment/commerce_native_digital_fulfillment_test.php',
            ],
            'bundle' => [
                '/local/subscriptions/classes/commerce/bundle/expansion/CommerceBundleExpansionService.php',
                '/local/subscriptions/tests/commerce/certification/commerce_795h49_bundle_purchase_certifier_test.php',
            ],
            'guest' => [
                '/local/subscriptions/guest_checkout.php',
                '/local/subscriptions/tests/commerce/checkout/commerce_795h54_guest_checkout_certification_test.php',
            ],
        ];
        foreach ($required as $family => $paths) {
            $missing = array_values(array_filter(
                $paths,
                static fn(string $path): bool => !is_readable($dirroot . $path)
            ));
            $report->add(new CommerceCustomerJourneyCertificationFinding(
                'journey.family.' . $family,
                $missing === [] ? CommerceCustomerJourneyCertificationFinding::OK : CommerceCustomerJourneyCertificationFinding::ERROR,
                ucfirst($family) . ' customer journey',
                $missing === [] ? 'Runtime and certification coverage available.' : 'Missing: ' . implode(', ', $missing)
            ));
        }
    }

    private function check_customer_pages(
        CommerceCustomerJourneyCertificationReport $report,
        string $dirroot
    ): void {
        $pages = [
            'Mes Achats' => '/local/subscriptions/my_purchases.php',
            'Mes Ressources' => '/local/subscriptions/my_digital_products.php',
            'Mes Cours' => '/local/campus/mycourses.php',
            'Order Details' => '/local/subscriptions/order_details.php',
            'Order Result' => '/local/subscriptions/order_result.php',
            'User 360' => '/local/subscriptions/admin/users/view.php',
        ];
        $missing = [];
        foreach ($pages as $label => $path) {
            if (!is_readable($dirroot . $path)) {
                $missing[] = $label . ' (' . $path . ')';
            }
        }
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'journey.customer_pages',
            $missing === [] ? CommerceCustomerJourneyCertificationFinding::OK : CommerceCustomerJourneyCertificationFinding::ERROR,
            'Customer and CRM pages',
            $missing === [] ? count($pages) . ' principal pages available.' : 'Missing: ' . implode(', ', $missing)
        ));
    }

    private function check_runtime_data_health(
        CommerceCustomerJourneyCertificationReport $report,
        int $now
    ): void {
        $manager = $this->db->get_manager();
        $requiredtables = [
            CommercePersistenceSchema::TABLE_PURCHASE,
            CommercePersistenceSchema::TABLE_ITEM,
            CommercePersistenceSchema::TABLE_PAYMENT,
            'local_subs_commerce_grant',
        ];
        foreach ($requiredtables as $table) {
            if (!$manager->table_exists($table)) {
                $report->add(new CommerceCustomerJourneyCertificationFinding(
                    'data.table.' . $table,
                    CommerceCustomerJourneyCertificationFinding::ERROR,
                    'Commerce data table ' . $table,
                    'Missing.'
                ));
                return;
            }
        }

        $purchases = $this->db->count_records(CommercePersistenceSchema::TABLE_PURCHASE);
        $fulfilled = $this->db->count_records(CommercePersistenceSchema::TABLE_PURCHASE, ['status' => 'fulfilled']);
        $pending = $this->db->count_records_select(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'status IN (:created, :pending, :fulfillmentpending) AND timecreated < :threshold',
            [
                'created' => 'created',
                'pending' => 'pending',
                'fulfillmentpending' => 'fulfillment_pending',
                'threshold' => $now - DAYSECS,
            ]
        );
        $failedgrants = $this->db->count_records_select(
            'local_subs_commerce_grant',
            'status IN (:failed, :error)',
            ['failed' => 'failed', 'error' => 'error']
        );
        $severity = ($pending > 0 || $failedgrants > 0)
            ? CommerceCustomerJourneyCertificationFinding::WARNING
            : CommerceCustomerJourneyCertificationFinding::OK;
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'data.health',
            $severity,
            'Operational data health',
            sprintf('purchases=%d fulfilled=%d stale_pending=%d failed_grants=%d', $purchases, $fulfilled, $pending, $failedgrants)
        ));
    }

    private function check_test_matrix(
        CommerceCustomerJourneyCertificationReport $report,
        string $dirroot
    ): void {
        $tests = [
            '/local/subscriptions/tests/commerce/checkout/commerce_795h54_guest_checkout_certification_test.php',
            '/local/subscriptions/tests/commerce/certification/commerce_795h47_course_purchase_certifier_test.php',
            '/local/subscriptions/tests/commerce/certification/commerce_795h48_digital_purchase_certifier_test.php',
            '/local/subscriptions/tests/commerce/certification/commerce_795h49_bundle_purchase_certifier_test.php',
            '/local/subscriptions/tests/commerce/mail/commerce_mail_engine_end_to_end_test.php',
            '/local/subscriptions/tests/commerce/course/commerce_my_courses_certification_test.php',
            '/local/subscriptions/tests/commerce/customer/commerce_customer_crm_certification_test.php',
            '/local/subscriptions/tests/commerce/professional/commerce_customer_experience_certification_test.php',
            '/local/campus/tests/mycourses/my_courses_release_certification_test.php',
        ];
        $missing = array_values(array_filter(
            $tests,
            static fn(string $path): bool => !is_readable($dirroot . $path)
        ));
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'journey.test_matrix',
            $missing === [] ? CommerceCustomerJourneyCertificationFinding::OK : CommerceCustomerJourneyCertificationFinding::ERROR,
            'Automated certification matrix',
            $missing === [] ? count($tests) . ' key certification tests available.' : 'Missing: ' . implode(', ', $missing)
        ));
    }

    private function check_plugin_versions(
        CommerceCustomerJourneyCertificationReport $report,
        string $dirroot
    ): void {
        $subscriptions = $this->read_file($dirroot . '/local/subscriptions/version.php');
        $campus = $this->read_file($dirroot . '/local/campus/version.php');
        $ok = str_contains($subscriptions, 'MATURITY_STABLE') && str_contains($campus, 'MATURITY_STABLE');
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'journey.plugin_versions',
            $ok ? CommerceCustomerJourneyCertificationFinding::OK : CommerceCustomerJourneyCertificationFinding::WARNING,
            'Plugin release maturity',
            $ok ? 'local_subscriptions and local_campus are marked stable.' : 'One plugin is not marked MATURITY_STABLE.'
        ));
    }

    /** @param string[] $paths */
    private function read_sources(array $paths): string {
        return implode("\n", array_map(fn(string $path): string => $this->read_file($path), $paths));
    }

    private function read_file(string $path): string {
        return is_readable($path) ? (string)file_get_contents($path) : '';
    }
}
