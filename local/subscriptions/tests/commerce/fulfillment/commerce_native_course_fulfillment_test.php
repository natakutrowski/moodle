<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessGrant;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseCohortService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseEnrolmentService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseGroupService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseRoleService;

/** @covers \local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler */
final class commerce_native_course_fulfillment_test extends advanced_testcase {
    public function test_course_grant_is_parsed_from_resource_and_configuration(): void {
        $access = CommerceCourseAccessGrant::from_grant($this->grant([
            'roleshortname' => 'grammarstudent',
            'cohortids' => [4, 4, 7],
            'cohortname' => 'AllUsers',
            'groupnames' => ['A1', 'A1'],
        ]));

        self::assertSame(13, $access->get_course_id());
        self::assertSame('full', $access->get_access_level());
        self::assertSame('grammarstudent', $access->get_role_shortname());
        self::assertSame([4, 7], $access->get_cohort_ids());
        self::assertSame(['AllUsers'], $access->get_cohort_names());
        self::assertSame(['A1'], $access->get_group_names());
    }

    public function test_handler_orchestrates_all_native_course_services(): void {
        $calls = [];
        $handler = $this->handler($calls);
        $result = $handler->fulfill(
            $this->grant(['roleshortname' => 'student', 'cohortid' => 3, 'groupname' => 'Full']),
            CommerceNativeFulfillmentContext::runtime('f2-runtime', time(), 2, 'phpunit')
        );

        self::assertTrue($result->is_completed());
        self::assertSame(['enrolment', 'role', 'cohorts', 'groups'], array_keys($calls));
        self::assertSame(13, $result->get_payload()['courseid']);
        self::assertSame('student', $result->get_payload()['roleshortname']);
    }

    public function test_dry_run_reaches_every_service_without_mutation_contract(): void {
        $calls = [];
        $result = $this->handler($calls)->fulfill(
            $this->grant(['groupname' => 'A1']),
            CommerceNativeFulfillmentContext::dry_run('f2-dryrun', time(), null, 'phpunit')
        );

        self::assertTrue($result->is_skipped());
        self::assertTrue($result->get_payload()['dryrun']);
        foreach ($calls as $call) {
            self::assertTrue($call['dryrun']);
        }
    }

    public function test_missing_beneficiary_user_id_is_rejected(): void {
        $grant = new CommerceEntitlementGrant(
            'grant-no-user', 'purchase-f2', 'item-f2', 'COURSE.A1', 'course_access',
            'course:13:full', 1, null, 'student@example.com', time()
        );

        $this->expectException(\coding_exception::class);
        $calls = [];
        $this->handler($calls)->fulfill(
            $grant,
            CommerceNativeFulfillmentContext::runtime('f2-no-user', time(), null, 'phpunit')
        );
    }

    public function test_resource_and_configuration_course_mismatch_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommerceCourseAccessGrant::from_grant($this->grant(['courseid' => 17]));
    }

    private function handler(array &$calls): CommerceCourseAccessFulfillmentHandler {
        $enrolments = new class($calls) implements CommerceCourseEnrolmentService {
            public function __construct(private array &$calls) {}
            public function apply(int $userid, int $courseid, string $roleshortname, int $validfrom, ?int $validuntil, bool $dryrun): array {
                $this->calls['enrolment'] = compact('userid', 'courseid', 'roleshortname', 'validfrom', 'validuntil', 'dryrun');
                return ['status' => $dryrun ? 'would_create' : 'created'];
            }
        };
        $roles = new class($calls) implements CommerceCourseRoleService {
            public function __construct(private array &$calls) {}
            public function apply(int $userid, int $courseid, string $roleshortname, bool $dryrun): array {
                $this->calls['role'] = compact('userid', 'courseid', 'roleshortname', 'dryrun');
                return ['status' => $dryrun ? 'would_assign' : 'assigned'];
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

    private function grant(array $configuration = []): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-f2', 'purchase-f2', 'item-f2', 'COURSE.A1.FULL', 'course_access',
            'course:13:full', 1, 2, 'student@example.com', time(), null, $configuration
        );
    }
}
