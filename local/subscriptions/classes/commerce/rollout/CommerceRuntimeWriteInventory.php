<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\rollout;

defined('MOODLE_INTERNAL') || die();

/**
 * Domain-aware inventory for Commerce database writes.
 *
 * Unlike the historical I10E scanner, this scanner does not classify every
 * database write in local_subscriptions as Commerce Legacy. It scopes findings
 * using Commerce table names and known Commerce runtime paths, then separates:
 * - native persistence writes;
 * - protected Legacy projections required for upgrade/rollback compatibility;
 * - operational Commerce writes (email markers, logs, reminders);
 * - genuine migration candidates.
 */
final class CommerceRuntimeWriteInventory {
    public const CLASS_NATIVE = 'native_persistence';
    public const CLASS_COMPATIBILITY = 'legacy_compatibility_projection';
    public const CLASS_OPERATIONAL = 'commerce_operational';
    public const CLASS_CATALOG_ADMIN = 'catalog_and_configuration';
    public const CLASS_MIGRATION_CANDIDATE = 'migration_candidate';

    private const EXCLUDED = ['/tests/', '/db/', '/cli/', '/vendor/', '/node_modules/'];

    private const NATIVE_TABLES = [
        'local_subscriptions_commerce_purchase',
        'local_subscriptions_commerce_purchase_item',
        'local_subscriptions_commerce_payment',
        'local_subscriptions_commerce_fulfillment',
        'local_subs_commerce_idem',
        'local_subs_commerce_cron_run',
    ];

    private const LEGACY_COMMERCE_TABLES = [
        'user_subscription',
        'subscription_payment_request',
        'subscription_digital_payment_request',
        'subscription_digital_product',
        'subscription_digital_product_lang',
        'subscription_event',
        'subscription_reminder_log',
        'subscription_plan',
        'subscription_plan_price',
        'subscription_plan_translation',
        'subscription_plan_entitlement',
        'subscription_plan_upgrade',
        'subscription_access_scope',
        'subscription_access_scope_translation',
    ];

    private const OPERATIONAL_TABLES = [
        'local_subscriptions_admin_log',
        'local_subscriptions_admin_tool_run',
        'local_subs_contact_reply',
    ];

    private const NATIVE_MARKERS = [
        '/classes/commerce/command/',
        '/classes/commerce/dualwrite/',
        '/classes/commerce/reconciliation/',
        '/classes/commerce/persistence/',
        '/classes/commerce/idempotency/',
    ];

    /**
     * Runtime files allowed to maintain the Legacy projection while PRE-PROD
     * keeps upgrade and rollback compatibility. This is deliberately explicit.
     */
    private const COMPATIBILITY_PATHS = [
        '/classes/commerce/checkout/CommerceCheckoutPersistenceService.php',
        '/classes/commerce/fulfillment/digital/LegacyDigitalFulfillmentGateway.php',
        '/classes/commerce/fulfillment/subscription/LegacySubscriptionFulfillmentGateway.php',
        '/classes/commerce/payment/legacy/',
        '/classes/commerce/payment/provider/alfa/LegacyAlfaPaymentGateway.php',
        '/classes/commerce/payment/provider/stripe/LegacyStripePaymentGateway.php',
        '/classes/commerce/legacy/',
        '/classes/commerce/migration/',
        '/classes/domain/PaymentService.php',
        '/classes/domain/SubscriptionService.php',
        '/classes/subscription_manager.php',
        '/classes/digital/digital_payment_service.php',
        '/classes/digital/repositories/DigitalPurchaseAdminActionRepository.php',
        '/classes/payment/alfa/AlfaGateway.php',
        '/classes/service/SubscriptionLifecycleService.php',
        '/payment/create_session.php',
        '/payment/digital_create_session.php',
        '/payment/retry_payment.php',
        '/payment/return.php',
        '/payment_success.php',
        '/payment_cancel.php',
        '/admin/digital/purchases/index.php',
        '/admin/subscriptions/delete.php',
        '/lib/user_subs_lib.php',
    ];

    private const CATALOG_ADMIN_PATHS = [
        '/admin/manage.php',
        '/admin/digital/products/',
        '/admin/plans/',
        '/lib/plans_lib.php',
        '/lib/scopes_lib.php',
        '/classes/external/toggle_plan.php',
        '/tabs/plans.php',
    ];

    private const OPERATIONAL_PATHS = [
        '/classes/commerce/fulfillment/postaction/',
        '/classes/commerce/task/',
        '/classes/service/DigitalPurchaseEmailService.php',
        '/classes/service/UserSubscriptionEmailService.php',
        '/classes/mailer.php',
        '/classes/log/EventLogger.php',
        '/classes/admin/AdminLog.php',
    ];

    public function scan(string $pluginroot): array {
        $pluginroot = rtrim(str_replace('\\', '/', $pluginroot), '/');
        $findings = [];
        $directory = new \RecursiveDirectoryIterator(
            $pluginroot,
            \FilesystemIterator::SKIP_DOTS
        );

        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $relative = substr($path, strlen($pluginroot));
            if ($this->contains_any($relative, self::EXCLUDED)) {
                continue;
            }
            $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lines as $index => $line) {
                $operationpattern = '/\\$DB->(insert_record|update_record|delete_records|delete_records_select|set_field|execute)\\s*\\(/';

                if (!preg_match($operationpattern, $line, $match)) {
                    continue;
                }
                $table = $this->extract_table($line);

                if (!$this->is_commerce_write($relative, $table)) {
                    continue;
                }
                [$classification, $reason] = $this->classify($relative, $table);

                $findings[] = new CommerceRuntimeWriteFinding(
                    ltrim($relative, '/'),
                    $index + 1,
                    $match[1],
                    $classification,
                    $table,
                    $reason
                );
            }
        }

        return $findings;
    }

    public function migration_candidates(array $findings): array {
        return array_values(array_filter(
            $findings,
            static fn(CommerceRuntimeWriteFinding $finding): bool =>
                $finding->get_classification() === self::CLASS_MIGRATION_CANDIDATE
        ));
    }

    /** @deprecated Use migration_candidates(). */
    public function direct_legacy(array $findings): array {
        return $this->migration_candidates($findings);
    }

    public function count_by_classification(array $findings): array {
        $counts = [
            self::CLASS_NATIVE => 0,
            self::CLASS_COMPATIBILITY => 0,
            self::CLASS_OPERATIONAL => 0,
            self::CLASS_CATALOG_ADMIN => 0,
            self::CLASS_MIGRATION_CANDIDATE => 0,
        ];
        foreach ($findings as $finding) {
            $counts[$finding->get_classification()] = ($counts[$finding->get_classification()] ?? 0) + 1;
        }

        return $counts;
    }

    private function classify(string $relative, ?string $table): array {
        $isnativetable = $table !== null
            && in_array($table, self::NATIVE_TABLES, true);

        if ($isnativetable || $this->contains_any($relative, self::NATIVE_MARKERS)) {
            return [self::CLASS_NATIVE, 'Native Commerce persistence infrastructure'];
        }
        if ($this->contains_any($relative, self::CATALOG_ADMIN_PATHS)) {
            return [
                self::CLASS_CATALOG_ADMIN,
                'Product catalogue or Commerce configuration, not a Purchase runtime projection',
            ];
        }
        $isoperationaltable = $table !== null
            && in_array($table, self::OPERATIONAL_TABLES, true);

        if ($this->contains_any($relative, self::OPERATIONAL_PATHS) || $isoperationaltable) {
            return [
                self::CLASS_OPERATIONAL,
                'Operational Commerce state, not a Purchase projection',
            ];
        }
        if ($this->contains_any($relative, self::COMPATIBILITY_PATHS)) {
            return [
                self::CLASS_COMPATIBILITY,
                'Protected Legacy projection retained for upgrade and rollback',
            ];
        }
        return [
            self::CLASS_MIGRATION_CANDIDATE,
            'Commerce write outside approved Native or compatibility paths',
        ];
    }

    private function is_commerce_write(string $relative, ?string $table): bool {
        $commercetables = array_merge(
            self::NATIVE_TABLES,
            self::LEGACY_COMMERCE_TABLES,
            self::OPERATIONAL_TABLES
        );

        if ($table !== null && in_array($table, $commercetables, true)) {
            return true;
        }
        return str_contains($relative, '/classes/commerce/');
    }

    private function extract_table(string $line): ?string {
        if (preg_match('/\\$DB->(?:insert_record|update_record|delete_records|delete_records_select|set_field)\\s*\\(\\s*[\'\"]([^\'\"]+)[\'\"]/', $line, $match)) {
            return $match[1];
        }

        return null;
    }

    private function contains_any(string $value, array $needles): bool {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }
}
