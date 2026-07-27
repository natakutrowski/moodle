<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseCohortService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseEnrolmentService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseGroupService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseRoleService;

/** Certifies phases 7.94F2-F3 without changing Moodle data. */
final class CommerceNativeCourseFulfillmentAuditor {
    public function __construct(private readonly string $plugindir) {}

    public function audit(): array {
        $errors = [];
        $calls = [];
        $handler = $this->handler($calls);
        $executor = new CommerceNativeFulfillmentExecutor(new CommerceNativeFulfillmentHandlerRegistry([$handler]));
        $result = $executor->execute($this->grant(), CommerceNativeFulfillmentContext::dry_run(
            'audit-f2-f3', time(), null, 'audit'
        ));

        $checks = [
            'handler' => $handler->get_grant_type() === 'course_access',
            'dryrun' => $result->is_skipped() && (bool) ($result->get_payload()['dryrun'] ?? false),
            'enrolment' => isset($calls['enrolment']),
            'roles' => isset($calls['role']),
            'cohorts' => isset($calls['cohorts']),
            'groups' => isset($calls['groups']),
            'nativeonly' => $this->check_native_only($errors),
        ];

        foreach ($checks as $name => $passed) {
            if (!$passed) {
                $errors[] = 'Native course fulfillment check failed: ' . $name;
            }
        }

        return [
            'checks' => $checks,
            'errors' => array_values(array_unique($errors)),
            'certified' => !in_array(false, $checks, true) && $errors === [],
        ];
    }

    private function check_native_only(array &$errors): bool {
        $directory = $this->plugindir . '/classes/commerce/fulfillment/native/course';
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
        return true;
    }

    private function handler(array &$calls): CommerceCourseAccessFulfillmentHandler {
        $enrolments = new class($calls) implements CommerceCourseEnrolmentService {
            public function __construct(private array &$calls) {}
            public function apply(int $userid, int $courseid, string $roleshortname, int $validfrom, ?int $validuntil, bool $dryrun): array {
                $this->calls['enrolment'] = compact('userid', 'courseid', 'roleshortname', 'validfrom', 'validuntil', 'dryrun');
                return ['status' => 'would_create'];
            }
        };
        $roles = new class($calls) implements CommerceCourseRoleService {
            public function __construct(private array &$calls) {}
            public function apply(int $userid, int $courseid, string $roleshortname, bool $dryrun): array {
                $this->calls['role'] = compact('userid', 'courseid', 'roleshortname', 'dryrun');
                return ['status' => 'would_assign'];
            }
        };
        $cohorts = new class($calls) implements CommerceCourseCohortService {
            public function __construct(private array &$calls) {}
            public function apply(int $userid, array $cohortids, array $cohortnames, bool $dryrun): array {
                $this->calls['cohorts'] = compact('userid', 'cohortids', 'cohortnames', 'dryrun');
                return [];
            }
        };
        $groups = new class($calls) implements CommerceCourseGroupService {
            public function __construct(private array &$calls) {}
            public function apply(int $userid, int $courseid, array $groupids, array $groupnames, bool $dryrun): array {
                $this->calls['groups'] = compact('userid', 'courseid', 'groupids', 'groupnames', 'dryrun');
                return [];
            }
        };
        return new CommerceCourseAccessFulfillmentHandler($enrolments, $roles, $cohorts, $groups);
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'audit-grant-f2', 'audit-purchase-f2', 'audit-item-f2', 'AUDIT.COURSE',
            'course_access', 'course:13:full', 1, 2, 'audit@example.com', time(), null,
            ['roleshortname' => 'student', 'cohortname' => 'AllUsers', 'groupname' => 'Audit']
        );
    }
}
