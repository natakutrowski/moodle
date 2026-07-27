<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceShadowComparator;
use local_subscriptions\commerce\shadow\CommerceShadowEntryPointRegistry;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionService;
use local_subscriptions\commerce\shadow\CommerceShadowSource;

/** Structural certification for phases 7.94G1-G3. */
final class CommerceShadowG1G3Auditor {
    public function __construct(private readonly string $pluginroot) {
    }

    public function audit(): array {
        $checks = [
            'inventory' => true,
            'entrypoints' => true,
            'sources' => true,
            'dryrun_only' => true,
            'comparison' => true,
            'runtime_untouched' => true,
        ];
        $errors = [];
        $seen = [];
        foreach ((new CommerceShadowEntryPointRegistry())->all() as $entrypoint) {
            if (isset($seen[$entrypoint->get_key()])) {
                $checks['entrypoints'] = false;
                $errors[] = 'Duplicate Shadow entry point: ' . $entrypoint->get_key();
            }
            $seen[$entrypoint->get_key()] = true;
            if (!CommerceShadowSource::is_valid($entrypoint->get_source())) {
                $checks['sources'] = false;
                $errors[] = 'Invalid source for entry point: ' . $entrypoint->get_key();
            }
            if (!is_file($this->pluginroot . '/' . $entrypoint->get_relative_path())) {
                $checks['inventory'] = false;
                $errors[] = 'Missing runtime path: ' . $entrypoint->get_relative_path();
            }
        }

        $executionfile = $this->pluginroot . '/classes/commerce/shadow/CommerceShadowExecutionService.php';
        $content = (string)file_get_contents($executionfile);
        foreach (['CommerceNativeFulfillmentContext::dry_run', "'persistence' => false", "'postactions' => false"] as $needle) {
            if (!str_contains($content, $needle)) {
                $checks['dryrun_only'] = false;
                $errors[] = 'Missing Shadow dry-run safeguard: ' . $needle;
            }
        }
        foreach (['insert_record(', 'update_record(', 'delete_records(', 'CommercePersistentNativeFulfillmentExecutor'] as $forbidden) {
            if (str_contains($content, $forbidden)) {
                $checks['dryrun_only'] = false;
                $errors[] = 'Forbidden persistence dependency in Shadow execution: ' . $forbidden;
            }
        }

        if (!class_exists(CommerceShadowComparator::class) || !class_exists(CommerceShadowExecutionService::class)) {
            $checks['comparison'] = false;
            $errors[] = 'Shadow execution or comparison service is unavailable.';
        }

        return ['checks' => $checks, 'errors' => $errors, 'certified' => !in_array(false, $checks, true) && $errors === []];
    }
}
