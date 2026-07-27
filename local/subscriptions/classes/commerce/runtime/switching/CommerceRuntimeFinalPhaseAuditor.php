<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService;

defined('MOODLE_INTERNAL') || die();

/**
 * Final certification auditor for phase 7.94H.
 */
final class CommerceRuntimeFinalPhaseAuditor {
    public function audit(): array {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $requiredfiles = [
            'classes/commerce/runtime/switching/CommerceRuntimeMode.php',
            'classes/commerce/runtime/switching/CommerceRuntimeConfiguration.php',
            'classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php',
            'classes/commerce/runtime/switching/CommerceNativeRuntimeExecutor.php',
            'classes/commerce/runtime/switching/CommerceRuntimeValidationMatrix.php',
            'classes/payment/EventRouter.php',
            'cli/commerce/operations/set_commerce_runtime_mode.php',
            'cli/commerce/operations/rollback_commerce_runtime.php',
            'tests/commerce/runtime/commerce_runtime_switch_test.php',
            'tests/commerce/runtime/commerce_runtime_functional_validation_test.php',
        ];

        $checks = [];
        $checks['configuration'] = $this->files_exist($root, array_slice($requiredfiles, 0, 2));
        $checks['runtime'] = $this->files_exist($root, array_slice($requiredfiles, 2, 4));
        $checks['rollback'] = $this->files_exist($root, array_slice($requiredfiles, 6, 2));
        $checks['validation_tests'] = $this->files_exist($root, array_slice($requiredfiles, 8, 2));

        $router = $this->read($root . '/classes/payment/EventRouter.php');
        $dispatcher = $this->read($root . '/classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php');
        $settings = $this->read($root . '/settings.php');

        $checks['dispatcher_wired'] = str_contains($router, 'CommerceRuntimeDispatcher');
        $checks['single_runtime_entrypoint'] = substr_count($router, 'checkout_completed(') >= 2
            && !str_contains($router, 'CommerceNativeRuntimeExecutor');
        $checks['legacy_mode'] = str_contains($dispatcher, 'CommerceRuntimeMode::LEGACY');
        $checks['shadow_mode'] = str_contains($dispatcher, 'CommerceRuntimeMode::SHADOW');
        $checks['native_mode'] = str_contains($dispatcher, 'CommerceNativeRuntimeExecutor');
        $checks['fallback_guard'] = str_contains($dispatcher, 'native_fallback_enabled()');
        $checks['exception_propagation'] = str_contains($dispatcher, 'throw $exception');
        $checks['settings_present'] = str_contains($settings, 'commerce_runtime_mode')
            && str_contains($settings, 'commerce_runtime_native_fallback_enabled');
        $checks['rollback_forces_legacy'] = $this->rollback_targets_safe_legacy();
        $checks['scenario_matrix_complete'] = CommerceRuntimeValidationMatrix::count() >= 20;
        $checks['safe_default'] = CommerceRuntimeMode::normalize(null) === CommerceRuntimeMode::LEGACY;

        $errors = count(array_filter($checks, static fn(bool $ok): bool => !$ok));

        return [
            'phase' => '7.94H',
            'checks' => $checks,
            'scenario_count' => CommerceRuntimeValidationMatrix::count(),
            'errors' => $errors,
            'certified' => $errors === 0,
        ];
    }

    /**
     * Verifies the guarded rollback target through its public read-only contract.
     */
    private function rollback_targets_safe_legacy(): bool {
        try {
            $inspection = (new CommerceRuntimeRollbackService())->inspect();
            $target = $inspection['target'] ?? [];

            return ($target['runtime_mode'] ?? null) === CommerceRuntimeMode::LEGACY
                && ($target['native_fallback_enabled'] ?? null) === true
                && ($target['shadow_enabled'] ?? null) === false
                && ($inspection['data_changes'] ?? null) === false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function files_exist(string $root, array $files): bool {
        foreach ($files as $file) {
            if (!is_file($root . '/' . $file)) {
                return false;
            }
        }
        return true;
    }

    private function read(string $path): string {
        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}
