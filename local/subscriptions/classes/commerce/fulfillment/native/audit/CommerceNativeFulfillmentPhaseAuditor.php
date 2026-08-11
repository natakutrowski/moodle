<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\audit;

defined('MOODLE_INTERNAL') || die();

/** Static architecture certification for the complete Native fulfillment phase. */
final class CommerceNativeFulfillmentPhaseAuditor {
    public function run(): array {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/classes/commerce/fulfillment/native';
        $required = [
            'CommerceNativeFulfillmentHandler.php',
            'CommerceNativeFulfillmentHandlerRegistry.php',
            'persistence/CommercePersistentNativeFulfillmentExecutor.php',
            'course/CommerceCourseAccessFulfillmentHandler.php',
            'digital/CommerceDigitalDownloadFulfillmentHandler.php',
            'batch/CommerceNativePurchaseFulfillmentOrchestrator.php',
            'postaction/CommerceNativePostFulfillmentCoordinator.php',
        ];
        $checks = [
            'kernel' => true,
            'course' => true,
            'digital' => true,
            'persistence' => true,
            'batch' => true,
            'postactions' => true,
            'nativeonly' => true,
        ];
        $errors = [];

        foreach ($required as $relative) {
            if (!is_file($root . '/' . $relative)) {
                $errors[] = 'Missing Native fulfillment class: ' . $relative;
            }
        }
        $checks['kernel'] = is_file($root . '/CommerceNativeFulfillmentHandler.php');
        $checks['course'] = is_file($root . '/course/CommerceCourseAccessFulfillmentHandler.php');
        $checks['digital'] = is_file($root . '/digital/CommerceDigitalDownloadFulfillmentHandler.php');
        $checks['persistence'] = is_file($root . '/persistence/CommercePersistentNativeFulfillmentExecutor.php');
        $checks['batch'] = is_file($root . '/batch/CommerceNativePurchaseFulfillmentOrchestrator.php');
        $checks['postactions'] = is_file($root . '/postaction/CommerceNativePostFulfillmentCoordinator.php');

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            if (str_contains($path, DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR)) {
                continue;
            }
            $contents = (string)file_get_contents($path);
            foreach ([
                'LegacySubscriptionFulfillmentGateway',
                'LegacyDigitalFulfillmentGateway',
                'subscription_manager',
            ] as $symbol) {
                $allowedtrialcleanup = $symbol === 'subscription_manager'
                    && basename($path) === 'MoodleCourseRoleService.php'
                    && str_contains(
                        $contents,
                        'cleanup_trial_subscription_if_unused'
                    );
                if (str_contains($contents, $symbol) && !$allowedtrialcleanup) {
                    $checks['nativeonly'] = false;
                    $errors[] = 'Forbidden dependency in Native fulfillment: ' . basename($path);
                    break;
                }
            }
        }

        return [
            'checks' => $checks,
            'errors' => $errors,
            'certified' => $errors === [] && !in_array(false, $checks, true),
        ];
    }
}
