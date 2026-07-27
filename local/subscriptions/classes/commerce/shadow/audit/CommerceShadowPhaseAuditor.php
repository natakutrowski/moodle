<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\audit;

defined('MOODLE_INTERNAL') || die();

/** Final structural certification for phase 7.94G Native Fulfillment Shadow. */
final class CommerceShadowPhaseAuditor {
    public function __construct(private readonly string $pluginroot) {
    }

    public function audit(): array {
        $checks = [
            'inventory' => true,
            'execution' => true,
            'comparison' => true,
            'persistence' => true,
            'runtime' => true,
            'statistics' => true,
            'search' => true,
            'export' => true,
            'dryrun' => true,
            'non_blocking' => true,
            'legacy_authority' => true,
            'schema' => true,
        ];
        $errors = [];

        $required = [
            'classes/commerce/shadow/CommerceShadowEntryPointRegistry.php' => 'inventory',
            'classes/commerce/shadow/CommerceShadowExecutionService.php' => 'execution',
            'classes/commerce/shadow/CommerceShadowComparator.php' => 'comparison',
            'classes/commerce/shadow/persistence/MoodleCommerceShadowPersistenceRepository.php' => 'persistence',
            'classes/commerce/shadow/runtime/CommerceShadowRuntimeHook.php' => 'runtime',
            'classes/commerce/shadow/reporting/CommerceShadowStatisticsService.php' => 'statistics',
            'classes/commerce/shadow/reporting/CommerceShadowSearchService.php' => 'search',
            'classes/commerce/shadow/reporting/CommerceShadowReportExporter.php' => 'export',
        ];
        foreach ($required as $relative => $check) {
            if (!is_file($this->pluginroot . '/' . $relative)) {
                $checks[$check] = false;
                $errors[] = 'Missing Shadow component: ' . $relative;
            }
        }

        $execution = $this->read('classes/commerce/shadow/CommerceShadowExecutionService.php');
        $kernel = $this->read('classes/commerce/shadow/CommerceKernelShadowNativeExecutor.php');
        if (!str_contains($execution, 'shadow') || !str_contains($kernel, 'CommerceNativeFulfillmentExecutor')) {
            $checks['dryrun'] = false;
            $errors[] = 'Shadow execution is not certified as non-persistent Native dry-run.';
        }
        if (str_contains($kernel, 'CommercePersistentNativeFulfillmentExecutor')) {
            $checks['dryrun'] = false;
            $errors[] = 'Persistent Native executor is forbidden in Shadow execution.';
        }

        $hook = $this->read('classes/commerce/shadow/runtime/CommerceShadowRuntimeHook.php');
        if (!str_contains($hook, 'catch (\\Throwable')) {
            $checks['non_blocking'] = false;
            $errors[] = 'Shadow runtime hook must catch all Throwables.';
        }
        if (!str_contains($hook, 'commerce_fulfillment_shadow_enabled')) {
            $checks['legacy_authority'] = false;
            $errors[] = 'Shadow feature flag is missing.';
        }

        $router = $this->read('classes/payment/EventRouter.php');
        $dispatcher = $this->read('classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php');
        $dualwrite = $this->read('classes/commerce/dualwrite/CommerceDualWriteBridge.php');
        if ((!str_contains($router, 'CommerceRuntimeDispatcher')
                || !str_contains($dispatcher, 'CommerceShadowRuntimeHook::after_checkout_completed'))
            && !str_contains($dualwrite, 'CommerceDualWriteShadowObserver::after_synchronise')) {
            $checks['runtime'] = false;
            $errors[] = 'Checkout runtime is not instrumented with the Shadow hook through the Runtime dispatcher.';
        }

        $installxml = $this->read('db/install.xml');
        if (!str_contains($installxml, 'TABLE NAME="local_subs_commerce_shadow"')) {
            $checks['schema'] = false;
            $errors[] = 'Shadow persistence table is missing from install.xml.';
        }

        return [
            'checks' => $checks,
            'errors' => $errors,
            'certified' => $errors === [] && !in_array(false, $checks, true),
        ];
    }

    private function read(string $relative): string {
        $path = $this->pluginroot . '/' . $relative;
        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}
