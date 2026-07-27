<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

defined('MOODLE_INTERNAL') || die();

final class CommerceRuntimePhaseAuditor {
    public function audit(): array {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $required = [
            'classes/commerce/runtime/switching/CommerceRuntimeMode.php',
            'classes/commerce/runtime/switching/CommerceRuntimeConfiguration.php',
            'classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php',
            'classes/commerce/runtime/switching/CommerceNativeRuntimeExecutor.php',
            'classes/payment/EventRouter.php',
        ];
        $checks = [];
        foreach ($required as $file) {
            $checks['file:' . basename($file)] = is_file($root . '/' . $file);
        }
        $router = (string) file_get_contents($root . '/classes/payment/EventRouter.php');
        $checks['dispatcher_wired'] = str_contains($router, 'CommerceRuntimeDispatcher');
        $checks['legacy_default'] = CommerceRuntimeMode::normalize(null) === CommerceRuntimeMode::LEGACY;
        $checks['rollback_cli'] = is_file($root . '/cli/commerce/operations/set_commerce_runtime_mode.php');
        $errors = count(array_filter($checks, static fn(bool $ok): bool => !$ok));
        return ['checks' => $checks, 'errors' => $errors, 'certified' => $errors === 0];
    }
}
