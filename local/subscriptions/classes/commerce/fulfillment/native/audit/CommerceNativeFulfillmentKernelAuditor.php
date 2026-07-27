<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Certifies the phase 7.94F1 Native fulfillment kernel. */
final class CommerceNativeFulfillmentKernelAuditor {
    public function __construct(private readonly string $plugindir) {
    }

    public function audit(): array {
        $errors = [];
        $checks = [
            'contracts' => $this->check_contracts($errors),
            'registry' => $this->check_registry($errors),
            'execution' => $this->check_execution($errors),
            'dryrun' => $this->check_dry_run($errors),
            'nativeonly' => $this->check_native_only($errors),
        ];

        return [
            'checks' => $checks,
            'errors' => $errors,
            'certified' => !in_array(false, $checks, true) && $errors === [],
        ];
    }

    private function check_contracts(array &$errors): bool {
        $classes = [
            CommerceNativeFulfillmentContext::class,
            CommerceNativeFulfillmentExecutor::class,
            CommerceNativeFulfillmentHandler::class,
            CommerceNativeFulfillmentHandlerRegistry::class,
            CommerceNativeFulfillmentResult::class,
        ];

        foreach ($classes as $class) {
            if (!class_exists($class) && !interface_exists($class)) {
                $errors[] = 'Missing Native fulfillment contract: ' . $class;
                return false;
            }
        }

        return true;
    }

    private function check_registry(array &$errors): bool {
        $handler = $this->test_handler();
        $registry = new CommerceNativeFulfillmentHandlerRegistry([$handler]);

        if (!$registry->supports('course_access')) {
            $errors[] = 'The Native registry cannot resolve a registered grant type.';
            return false;
        }

        try {
            $registry->register($handler);
            $errors[] = 'The Native registry accepted a duplicate grant handler.';
            return false;
        } catch (\coding_exception) {
            return true;
        }
    }

    private function check_execution(array &$errors): bool {
        $executor = new CommerceNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$this->test_handler()])
        );
        $result = $executor->execute($this->grant(), $this->context(false));

        if (!$result->is_completed()) {
            $errors[] = 'The Native executor did not complete a supported grant.';
            return false;
        }

        return true;
    }

    private function check_dry_run(array &$errors): bool {
        $executor = new CommerceNativeFulfillmentExecutor(
            new CommerceNativeFulfillmentHandlerRegistry([$this->test_handler()])
        );
        $result = $executor->execute($this->grant(), $this->context(true));

        if (!$result->is_skipped() || !($result->get_payload()['dryrun'] ?? false)) {
            $errors[] = 'The Native executor dry-run contract is not respected.';
            return false;
        }

        return true;
    }

    private function check_native_only(array &$errors): bool {
        $directory = $this->plugindir . '/classes/commerce/fulfillment/native';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // Audit helpers contain the forbidden dependency names as search patterns.
            // They are not part of the Native fulfillment runtime and must not audit themselves.
            $relativepath = str_replace('\\', '/', substr($file->getPathname(), strlen($directory)));
            if (str_starts_with($relativepath, '/audit/')) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match('/Legacy(?:Subscription|Digital|Fulfillment)|legacy\\\\/i', $contents)) {
                $errors[] = 'Legacy dependency found in Native fulfillment: ' . $file->getFilename();
                return false;
            }
        }

        return true;
    }

    private function test_handler(): CommerceNativeFulfillmentHandler {
        return new class implements CommerceNativeFulfillmentHandler {
            public function get_grant_type(): string {
                return 'course_access';
            }

            public function fulfill(
                CommerceEntitlementGrant $grant,
                CommerceNativeFulfillmentContext $context
            ): CommerceNativeFulfillmentResult {
                if ($context->is_dry_run()) {
                    return CommerceNativeFulfillmentResult::skipped(
                        $grant,
                        'Dry-run: no Moodle mutation executed.',
                        ['dryrun' => true]
                    );
                }

                return CommerceNativeFulfillmentResult::completed(
                    $grant,
                    ['resourcekey' => $grant->get_resource_key()]
                );
            }
        };
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'audit-grant-f1',
            'audit-purchase-f1',
            'audit-item-f1',
            'AUDIT.COURSE',
            'course_access',
            'course:13:full',
            1,
            2,
            'audit@example.com',
            time()
        );
    }

    private function context(bool $dryrun): CommerceNativeFulfillmentContext {
        return $dryrun
            ? CommerceNativeFulfillmentContext::dry_run('audit-f1-dryrun', time(), null, 'audit')
            : CommerceNativeFulfillmentContext::runtime('audit-f1-runtime', time(), null, 'audit');
    }
}
