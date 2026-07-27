<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\audit;

defined('MOODLE_INTERNAL') || die();

/** Structural audit for G4 persistence, G5 runtime hook and G6 classification. */
final class CommerceShadowG4G6Auditor {
    public function __construct(private readonly string $pluginroot) {}

    public function audit(): array {
        $required = [
            'classes/commerce/shadow/persistence/MoodleCommerceShadowPersistenceRepository.php',
            'classes/commerce/shadow/runtime/CommerceShadowRuntimeService.php',
            'classes/commerce/shadow/runtime/CommerceShadowRuntimeHook.php',
            'classes/commerce/shadow/CommerceShadowDivergenceClassifier.php',
        ];
        $checks = [
            'persistence' => true,
            'runtime_hook' => true,
            'classification' => true,
            'legacy_authority' => true,
            'non_blocking' => true,
            'install_schema' => true,
        ];
        $errors = [];

        foreach ($required as $file) {
            if (!is_file($this->pluginroot . '/' . $file)) {
                $errors[] = 'Missing G4-G6 component: ' . $file;
            }
        }

        $installxml = (string) file_get_contents($this->pluginroot . '/db/install.xml');
        if (!str_contains($installxml, 'TABLE NAME="local_subs_commerce_shadow"')) {
            $checks['install_schema'] = false;
            $errors[] = 'Shadow persistence table is missing from db/install.xml.';
        }

        $eventrouter = (string) file_get_contents($this->pluginroot . '/classes/payment/EventRouter.php');
        $dispatcher = (string) file_get_contents(
            $this->pluginroot . '/classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php'
        );
        $dualwrite = (string) file_get_contents(
            $this->pluginroot . '/classes/commerce/dualwrite/CommerceDualWriteBridge.php'
        );
        if ((!str_contains($eventrouter, 'CommerceRuntimeDispatcher')
                || !str_contains($dispatcher, 'CommerceShadowRuntimeHook::after_checkout_completed'))
            && !str_contains($dualwrite, 'CommerceDualWriteShadowObserver::after_synchronise')) {
            $checks['runtime_hook'] = false;
            $errors[] = 'EventRouter is not instrumented through the Runtime dispatcher.';
        }

        $hook = (string) file_get_contents($this->pluginroot . '/classes/commerce/shadow/runtime/CommerceShadowRuntimeHook.php');
        if (!str_contains($hook, 'catch (\\Throwable')) {
            $checks['non_blocking'] = false;
            $errors[] = 'Shadow runtime hook is not non-blocking.';
        }
        if (!str_contains($hook, 'commerce_fulfillment_shadow_enabled')) {
            $checks['legacy_authority'] = false;
            $errors[] = 'Shadow runtime feature flag is missing.';
        }

        return [
            'checks' => $checks,
            'errors' => $errors,
            'certified' => $errors === [] && !in_array(false, $checks, true),
        ];
    }
}
