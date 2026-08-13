<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalBulkProvisioningService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningService;

/**
 * Read-only final certification for Identity Operations M4.2.
 */
final class CommerceCustomerIdentityOperationsCertificationService {
    /**
     * @return array{
     *     status:string,
     *     errors:int,
     *     checks:array<int,array{ok:bool,scope:string,label:string,detail:string}>
     * }
     */
    public function certify(): array {
        global $CFG, $DB;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $checks = [];

        // M4.2A — advanced search + bulk reconciliation + dry-run.
        $search = $this->read(
            $root . 'classes/commerce/customer/reconciliation/'
                . 'CommerceCustomerIdentitySearchService.php'
        );
        $bulk = $this->read(
            $root . 'classes/commerce/customer/reconciliation/'
                . 'CommerceCustomerBulkReconciliationService.php'
        );
        $reconciliation = $this->read(
            $root . 'classes/commerce/customer/reconciliation/'
                . 'CommerceCustomerIdentityReconciliationService.php'
        );
        $checks[] = $this->check(
            str_contains($search, 'candidateuserid')
                && str_contains($search, 'purchaseid')
                && str_contains($search, 'reference')
                && str_contains($search, 'sku'),
            'M4.2A',
            'Advanced identity search',
            'Customer identities can be filtered by purchase, reference, SKU and candidate Moodle user.'
        );
        $checks[] = $this->check(
            str_contains($bulk, 'preview(')
                && str_contains($bulk, 'execute(')
                && str_contains($reconciliation, 'preview_purchase('),
            'M4.2A',
            'Bulk reconciliation dry-run',
            'Bulk reconciliation exposes an explicit read-only preview before execution.'
        );

        // M4.2B — similarity is advisory/read-only.
        $similarity = $this->read(
            $root . 'classes/commerce/customer/identity/'
                . 'CommerceCustomerIdentitySimilarityService.php'
        );
        $similaritypage = $this->read(
            $root . 'admin/commerce/customer-identities/similarities.php'
        );
        $checks[] = $this->check(
            CommerceCustomerIdentitySimilarityService::DEFAULT_MIN_SCORE === 60
                && str_contains($similarity, 'REASON_EMAIL_EXACT')
                && str_contains($similarity, 'REASON_NAME_EXACT')
                && str_contains($similarity, 'REASON_PHONE_EXACT')
                && str_contains($similarity, 'suggest_for_external_identity('),
            'M4.2B',
            'Transparent similarity engine',
            'Similarity exposes explicit email/name/phone signals and supports conservative external identity checks.'
        );
        $checks[] = $this->check(
            !str_contains($similaritypage, '$DB->update_record(')
                && !str_contains($similaritypage, '$DB->delete_records(')
                && !str_contains($similaritypage, 'user_delete_user('),
            'M4.2B',
            'Similarity remains advisory',
            'The similar-account screen does not write, suspend, delete or merge Moodle accounts.'
        );

        // M4.2C — dry-run merge planner, pedagogy first.
        $planner = $this->read(
            $root . 'classes/commerce/customer/merge/'
                . 'CommerceCustomerMergePlanner.php'
        );
        $mergepage = $this->read(
            $root . 'admin/commerce/customer-identities/merge.php'
        );
        $checks[] = $this->check(
            CommerceCustomerMergePlanner::MIN_ACCOUNTS === 2
                && CommerceCustomerMergePlanner::MAX_ACCOUNTS === 10
                && str_contains($planner, 'pedagogical_score()')
                && str_contains($planner, '$b->completedcourses')
                && str_contains($planner, '$b->completedactivities')
                && str_contains($planner, '$b->enrolledcourses')
                && str_contains($planner, '$b->averagegradepercent'),
            'M4.2C',
            'Pedagogy-first primary account recommendation',
            'The recommended account prioritises course completion, completed activities, enrolments and grades before Commerce/account tie-breakers.'
        );
        $checks[] = $this->check(
            str_contains($mergepage, 'CommerceCustomerMergePlanner')
                && str_contains($mergepage, 'commerce_identity_merge_virtual_profile')
                && str_contains($mergepage, 'targetuserid'),
            'M4.2C',
            'Virtual merge profile',
            'Administrators can preview the final account and manually override the recommended primary account.'
        );

        // M4.2D — transactional execution + hard safety blockers + audit.
        $execution = $this->read(
            $root . 'classes/commerce/customer/merge/'
                . 'CommerceCustomerMergeExecutionService.php'
        );
        $legacyconsolidation = $this->read(
            $root . 'classes/commerce/customer/merge/'
                . 'CommerceCustomerLegacyConsolidationService.php'
        );
        $dbman = $DB->get_manager();
        $mergetable = new \xmldb_table('local_subs_identity_merge');
        $sourcetable = new \xmldb_table('local_subs_identity_merge_source');

        $checks[] = $this->check(
            $dbman->table_exists($mergetable)
                && $dbman->table_exists($sourcetable),
            'M4.2D',
            'Merge audit schema installed',
            'Permanent merge and source-account audit tables exist in the current Moodle database.'
        );
        $checks[] = $this->check(
            str_contains($execution, 'start_delegated_transaction()')
                && str_contains($execution, 'BLOCK_PEDAGOGICAL_HISTORY')
                && str_contains($execution, 'BLOCK_LEGACY_SUBSCRIPTION')
                && str_contains($execution, 'BLOCK_ALREADY_MERGED')
                && str_contains($execution, 'user_update_user(')
                && !str_contains($execution, 'user_delete_user('),
            'M4.2D',
            'Transactional safe merge execution',
            'Merge execution is transactional, blocks unsafe histories and suspends rather than deletes source accounts.'
        );
        $checks[] = $this->check(
            str_contains($legacyconsolidation, "'local_subscriptions_commerce_purchase'")
                && str_contains($legacyconsolidation, "'local_subs_commerce_grant'")
                && str_contains($legacyconsolidation, "'local_subs_commerce_dig_access'")
                && str_contains($legacyconsolidation, "'subscription_digital_payment_request'")
                && str_contains($execution, "'local_subscriptions_user_note'")
                && str_contains($execution, "'local_subscriptions_user_tag'"),
            'M4.2D',
            'Certified Commerce/CRM transfers',
            'Native purchases, grants, digital access, Legacy Digital and core CRM identity data are explicitly covered.'
        );

        // M4.2E — Legacy Digital provisioning + activation.
        $provisioning = $this->read(
            $root . 'classes/commerce/customer/provisioning/'
                . 'CommerceLegacyDigitalProvisioningService.php'
        );
        $activation = $this->read(
            $root . 'classes/commerce/customer/provisioning/'
                . 'CommerceLegacyDigitalAccountActivationService.php'
        );
        $bulkprovisioning = $this->read(
            $root . 'classes/commerce/customer/provisioning/'
                . 'CommerceLegacyDigitalBulkProvisioningService.php'
        );
        $checks[] = $this->check(
            CommerceLegacyDigitalProvisioningService::MAX_SCAN_ROWS === 3000
                && CommerceLegacyDigitalBulkProvisioningService::MAX_BATCH === 500
                && str_contains($provisioning, 'plan_email(')
                && str_contains($provisioning, 'execute_email(')
                && str_contains($provisioning, 'STATUS_EXISTING_ACCOUNT')
                && str_contains($provisioning, 'STATUS_AMBIGUOUS_ACCOUNT')
                && str_contains($provisioning, 'STATUS_SIMILAR_ACCOUNT'),
            'M4.2E',
            'Legacy Digital provisioning dry-run',
            'Provisioning distinguishes safe, existing, ambiguous and similar-account cases before account creation.'
        );
        $checks[] = $this->check(
            str_contains($provisioning, "'confirmed' => 0")
                && str_contains($provisioning, "'suspended' => 1")
                && str_contains($provisioning, "'activation_pending'")
                && str_contains($provisioning, 'CommerceMailRuntime::queue_service()')
                && str_contains($activation, 'create_user_key(')
                && str_contains($activation, 'validate_user_key(')
                && str_contains($activation, 'delete_user_key(')
                && str_contains($activation, "'ready'"),
            'M4.2E',
            'Safe account activation lifecycle',
            'Provisioned accounts remain unusable until one-time activation, then become confirmed/active and ready.'
        );
        $checks[] = $this->check(
            str_contains($provisioning, 'translation_exists(')
                && str_contains($similarity, 'firstnamephonetic')
                && str_contains($similarity, 'lastnamephonetic')
                && str_contains($similarity, 'middlename')
                && str_contains($similarity, 'alternatename'),
            'M4.2E',
            'Moodle user-data compatibility',
            'Provisioning resolves installed user languages and similarity returns fullname-safe Moodle user records.'
        );

        // Cross-cutting — permissions, sesskey, CRM shell, locale coverage.
        $indexpage = $this->read(
            $root . 'admin/commerce/customer-identities/index.php'
        );
        $provisioningpage = $this->read(
            $root . 'admin/commerce/customer-identities/provisioning.php'
        );
        $navigation = $this->read(
            $root . 'classes/commerce/customer/identity/'
                . 'CommerceCustomerIdentityNavigationRenderer.php'
        );

        $checks[] = $this->check(
            str_contains($mergepage, 'Capabilities::MANAGE_USERS')
                && str_contains($provisioningpage, 'Capabilities::MANAGE_USERS')
                && str_contains($mergepage, 'require_sesskey()')
                && str_contains($provisioningpage, 'require_sesskey()'),
            'Cross-cutting',
            'Administrative write protection',
            'Merge and account creation require user-management capability and sesskey-protected writes.'
        );

        $checks[] = $this->check(
            str_contains($indexpage, 'CrmWorkspaceRenderer::start(')
                && str_contains($similaritypage, 'CrmWorkspaceRenderer::start(')
                && str_contains($mergepage, 'CrmWorkspaceRenderer::start(')
                && str_contains($provisioningpage, 'CrmWorkspaceRenderer::start(')
                && str_contains($navigation, "public const RECONCILIATION")
                && str_contains($navigation, "public const SIMILARITIES")
                && str_contains($navigation, "public const MERGE")
                && str_contains($navigation, "public const PROVISIONING"),
            'Cross-cutting',
            'Unified Identity Operations CRM workspace',
            'Reconciliation, similarities, merge and provisioning share the CRM shell and stable secondary navigation.'
        );

        $checks[] = $this->check(
            $this->language_keys_exist(
                $root,
                [
                    'commerce_identity_nav_reconciliation',
                    'commerce_identity_nav_similarities',
                    'commerce_identity_nav_merge',
                    'commerce_identity_nav_provisioning',
                    'commerce_identity_merge_title',
                    'commerce_identity_merge_execution_blocked',
                    'commerce_identity_provisioning_title',
                    'commerce_legacy_account_activation_title',
                ]
            ),
            'Cross-cutting',
            'FR / EN / RU Identity Operations strings',
            'Critical M4.2 interface strings exist in all three CampusFR locales.'
        );

        $errors = count(array_filter(
            $checks,
            static fn(array $check): bool => !$check['ok']
        ));

        return [
            'status' => $errors === 0 ? 'GREEN' : 'FAILED',
            'errors' => $errors,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{ok:bool,scope:string,label:string,detail:string}
     */
    private function check(
        bool $ok,
        string $scope,
        string $label,
        string $detail
    ): array {
        return [
            'ok' => $ok,
            'scope' => $scope,
            'label' => $label,
            'detail' => $detail,
        ];
    }

    private function read(string $path): string {
        return is_readable($path)
            ? (string)file_get_contents($path)
            : '';
    }

    /**
     * @param string[] $keys
     */
    private function language_keys_exist(
        string $root,
        array $keys
    ): bool {
        foreach (['fr', 'en', 'ru'] as $language) {
            $source = $this->read(
                $root . 'lang/' . $language . '/local_subscriptions.php'
            );
            foreach ($keys as $key) {
                if (!str_contains($source, "\$string['{$key}']")) {
                    return false;
                }
            }
        }

        return true;
    }
}
