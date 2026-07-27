<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalAccessRepository;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;

/** Certifies phases 7.94F4-F5 without mutating Commerce data. */
final class CommerceNativeDigitalPersistenceAuditor {
    public function __construct(private readonly string $plugindir) {}

    public function audit(): array {
        global $DB;
        $errors = [];
        $repository = new class implements CommerceDigitalAccessRepository {
            public int $writes = 0;
            public function find_by_grant_reference(string $grantreference): ?\stdClass { return null; }
            public function grant(CommerceEntitlementGrant $grant, string $token, ?int $maxdownloads, int $now): array {
                $this->writes++;
                return [];
            }
        };
        $handler = new CommerceDigitalDownloadFulfillmentHandler($repository);
        $result = $handler->fulfill($this->grant(), CommerceNativeFulfillmentContext::dry_run(
            'audit-f4-f5', time(), null, 'audit'
        ));

        $dbman = $DB->get_manager();
        $checks = [
            'handler' => $handler->get_grant_type() === 'digital_download',
            'dryrun' => $result->is_skipped() && $repository->writes === 0,
            'digitalaccess' => $dbman->table_exists('local_subs_commerce_dig_access'),
            'state' => $dbman->table_exists('local_subs_commerce_ful_state'),
            'attempts' => $dbman->table_exists('local_subs_commerce_ful_attempt'),
            'nativeonly' => $this->check_native_only($errors),
        ];
        foreach ($checks as $name => $passed) {
            if (!$passed) {
                $errors[] = 'Native digital/persistence check failed: ' . $name;
            }
        }
        return ['checks' => $checks, 'errors' => array_values(array_unique($errors)),
            'certified' => !in_array(false, $checks, true) && $errors === []];
    }

    private function check_native_only(array &$errors): bool {
        foreach (['digital', 'persistence'] as $subdir) {
            $directory = $this->plugindir . '/classes/commerce/fulfillment/native/' . $subdir;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $contents = (string) file_get_contents($file->getPathname());
                if (preg_match('/subscription_manager|Legacy|legacy\\\\/i', $contents)) {
                    $errors[] = 'Non-Native dependency found: ' . $file->getFilename();
                    return false;
                }
            }
        }
        return true;
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'audit-grant-f4', 'audit-purchase-f4', 'audit-item-f4', 'AUDIT.DIGITAL',
            'digital_download', 'digital-product:2', 1, 2, 'audit@example.com', time(), null,
            ['maxdownloads' => 3]
        );
    }
}
