<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\readiness;

use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only production-readiness audit for phase 7.94I1.
 */
final class CommerceProductionReadinessAuditor {
    private const ERROR = 'error';
    private const WARNING = 'warning';
    private const OK = 'ok';

    /** @return array<string, mixed> */
    public function audit(): array {
        global $CFG, $DB;

        $root = $CFG->dirroot . '/local/subscriptions';
        $checks = [];

        $mode = CommerceRuntimeMode::normalize((string)get_config('local_subscriptions', 'commerce_runtime_mode'));
        $fallback = (bool)get_config('local_subscriptions', 'commerce_runtime_native_fallback_enabled');
        $shadow = (bool)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled');

        $this->add($checks, 'runtime_mode_valid', in_array($mode, [
            CommerceRuntimeMode::LEGACY,
            CommerceRuntimeMode::SHADOW,
            CommerceRuntimeMode::NATIVE,
        ], true), self::ERROR, 'Runtime mode is valid.', 'Invalid Commerce Runtime mode.');
        $this->add($checks, 'runtime_predeployment_mode', $mode === CommerceRuntimeMode::SHADOW, self::WARNING,
            'Runtime is in Shadow mode.', 'Expected Shadow mode before the production switch; current mode: ' . $mode . '.');
        $this->add($checks, 'native_fallback_enabled', $fallback, self::ERROR,
            'Native fallback is enabled.', 'Native fallback must remain enabled for the production migration.');
        $this->add($checks, 'shadow_enabled', $shadow, self::WARNING,
            'Shadow execution is enabled.', 'Shadow should be enabled before production certification.');

        foreach ($this->required_files() as $name => $relativepath) {
            $this->add($checks, $name, is_file($root . '/' . $relativepath), self::ERROR,
                $relativepath . ' is present.', $relativepath . ' is missing.');
        }

        foreach ($this->required_classes() as $name => $classname) {
            $this->add($checks, $name, class_exists($classname), self::ERROR,
                $classname . ' is autoloadable.', $classname . ' cannot be autoloaded.');
        }

        $manager = $DB->get_manager();
        foreach ($this->required_tables() as $name => $tablename) {
            $this->add($checks, $name, $manager->table_exists(new xmldb_table($tablename)), self::ERROR,
                $tablename . ' exists.', $tablename . ' is missing.');
        }

        $tasks = $this->read($root . '/db/tasks.php');
        foreach ($this->required_task_markers() as $name => $marker) {
            $this->add($checks, $name, str_contains($tasks, $marker), self::ERROR,
                $marker . ' is scheduled.', $marker . ' is absent from db/tasks.php.');
        }

        $dispatcher = $this->read($root . '/classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php');
        $supportedmodes = CommerceRuntimeMode::all();
        $dispatchermethod = method_exists(
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeDispatcher::class,
            'checkout_completed'
        );
        $this->add($checks, 'runtime_dispatcher_modes',
            $supportedmodes === [
                CommerceRuntimeMode::LEGACY,
                CommerceRuntimeMode::SHADOW,
                CommerceRuntimeMode::NATIVE,
            ] && $dispatchermethod,
            self::ERROR,
            'Runtime mode registry declares Legacy, Shadow and Native and the Dispatcher entrypoint is available.',
            'Runtime mode registry or Dispatcher entrypoint coverage is incomplete.');
        $this->add($checks, 'runtime_dispatcher_fallback', str_contains($dispatcher, 'native_fallback_enabled'), self::ERROR,
            'Dispatcher contains the Native fallback guard.', 'Dispatcher Native fallback guard is missing.');

        $rollbackinspection = (new CommerceRuntimeRollbackService())->inspect();
        $rollbacktarget = $rollbackinspection['target'] ?? [];
        $this->add($checks, 'rollback_forces_legacy',
            ($rollbacktarget['runtime_mode'] ?? null) === CommerceRuntimeMode::LEGACY
                && ($rollbacktarget['native_fallback_enabled'] ?? null) === true
                && ($rollbacktarget['shadow_enabled'] ?? null) === false
                && ($rollbackinspection['data_changes'] ?? null) === false,
            self::ERROR,
            'Rollback service targets Legacy with fallback enabled, Shadow disabled and no Commerce data changes.',
            'Rollback service does not provide the expected safe Legacy configuration.');

        $errors = 0;
        $warnings = 0;
        foreach ($checks as $check) {
            if ($check['status'] === self::ERROR) {
                $errors++;
            } else if ($check['status'] === self::WARNING) {
                $warnings++;
            }
        }

        return [
            'phase' => '7.94I1',
            'readonly' => true,
            'runtime_mode' => $mode,
            'checks' => $checks,
            'errors' => $errors,
            'warnings' => $warnings,
            'ready' => $errors === 0,
        ];
    }

    /** @param array<string, array<string, mixed>> $checks */
    private function add(array &$checks, string $name, bool $passed, string $failurelevel,
            string $successmessage, string $failuremessage): void {
        $checks[$name] = [
            'status' => $passed ? self::OK : $failurelevel,
            'message' => $passed ? $successmessage : $failuremessage,
        ];
    }

    /** @return array<string, string> */
    public function required_files(): array {
        return [
            'cli_backfill' => 'cli/commerce/migration/migrate_legacy_commerce_purchases.php',
            'cli_backfill_audit' => 'cli/commerce/migration/audit_commerce_native_backfill.php',
            'cli_reconciliation' => 'cli/commerce/migration/reconcile_native_commerce.php',
            'cli_integrity_audit' => 'cli/commerce/audit/audit_commerce_integrity.php',
            'cli_runtime_switch' => 'cli/commerce/operations/set_commerce_runtime_mode.php',
            'cli_runtime_rollback' => 'cli/commerce/operations/rollback_commerce_runtime.php',
            'cli_h7_certification' => 'cli/commerce/certification/audit_commerce_runtime_h7.php',
            'cli_shadow_summary' => 'cli/commerce/reporting/audit_commerce_shadow_summary.php',
            'runtime_dispatcher' => 'classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php',
            'shadow_hook' => 'classes/commerce/shadow/runtime/CommerceShadowRuntimeHook.php',
            'legacy_migrator' => 'classes/commerce/migration/CommerceLegacyPurchaseMigrator.php',
        ];
    }

    /** @return array<string, class-string> */
    private function required_classes(): array {
        return [
            'class_runtime_dispatcher' => \local_subscriptions\commerce\runtime\switching\CommerceRuntimeDispatcher::class,
            'class_runtime_rollback_service' => CommerceRuntimeRollbackService::class,
            'class_native_executor' => \local_subscriptions\commerce\runtime\switching\CommerceNativeRuntimeExecutor::class,
            'class_shadow_hook' => \local_subscriptions\commerce\shadow\runtime\CommerceShadowRuntimeHook::class,
            'class_legacy_migrator' => \local_subscriptions\commerce\migration\CommerceLegacyPurchaseMigrator::class,
            'class_provider_registry' => \local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry::class,
            'class_fulfillment_registry' => \local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry::class,
        ];
    }

    /** @return array<string, string> */
    private function required_tables(): array {
        return [
            'table_legacy_subscription' => 'user_subscription',
            'table_legacy_digital' => 'subscription_digital_payment_request',
            'table_native_purchase' => 'local_subscriptions_commerce_purchase',
            'table_native_item' => 'local_subscriptions_commerce_purchase_item',
            'table_native_payment' => 'local_subscriptions_commerce_payment',
            'table_native_fulfillment' => 'local_subscriptions_commerce_fulfillment',
            'table_native_grant' => 'local_subs_commerce_grant',
            'table_native_shadow' => 'local_subs_commerce_shadow',
        ];
    }

    /** @return array<string, string> */
    private function required_task_markers(): array {
        return [
            'task_paid_request_repair' => 'repair_paid_pr_task',
            'task_digital_reconciliation' => 'reconcile_digital_payments_task',
        ];
    }

    private function read(string $path): string {
        return is_file($path) ? (string)file_get_contents($path) : '';
    }
}
