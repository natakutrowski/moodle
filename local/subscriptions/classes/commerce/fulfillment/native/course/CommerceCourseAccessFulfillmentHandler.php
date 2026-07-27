<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseCohortService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseEnrolmentService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseGroupService;
use local_subscriptions\commerce\fulfillment\native\course\service\CommerceCourseRoleService;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseCohortService;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseEnrolmentService;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseGroupService;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseRoleService;

/** Executes course_access grants directly against Moodle. */
final class CommerceCourseAccessFulfillmentHandler implements CommerceNativeFulfillmentHandler {
    public const GRANT_TYPE = 'course_access';

    public function __construct(
        private readonly CommerceCourseEnrolmentService $enrolments = new MoodleCourseEnrolmentService(),
        private readonly CommerceCourseRoleService $roles = new MoodleCourseRoleService(),
        private readonly CommerceCourseCohortService $cohorts = new MoodleCourseCohortService(),
        private readonly CommerceCourseGroupService $groups = new MoodleCourseGroupService()
    ) {
    }

    public function get_grant_type(): string {
        return self::GRANT_TYPE;
    }

    public function fulfill(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult {
        $userid = $grant->get_beneficiary_user_id();
        if ($userid === null) {
            throw new \coding_exception('Native course fulfillment requires a Moodle beneficiary user identifier.');
        }

        $access = CommerceCourseAccessGrant::from_grant($grant);
        $dryrun = $context->is_dry_run();
        $payload = [
            'idempotencykey' => $grant->get_idempotency_key(),
            'courseid' => $access->get_course_id(),
            'userid' => $userid,
            'accesslevel' => $access->get_access_level(),
            'roleshortname' => $access->get_role_shortname(),
            'validfrom' => $grant->get_valid_from(),
            'validuntil' => $grant->get_valid_until(),
        ];

        $payload['enrolment'] = $this->enrolments->apply(
            $userid,
            $access->get_course_id(),
            $access->get_role_shortname(),
            $grant->get_valid_from(),
            $grant->get_valid_until(),
            $dryrun
        );
        $payload['role'] = $this->roles->apply(
            $userid,
            $access->get_course_id(),
            $access->get_role_shortname(),
            $dryrun
        );
        $payload['cohorts'] = $this->cohorts->apply(
            $userid,
            $access->get_cohort_ids(),
            $access->get_cohort_names(),
            $dryrun
        );
        $payload['groups'] = $this->groups->apply(
            $userid,
            $access->get_course_id(),
            $access->get_group_ids(),
            $access->get_group_names(),
            $dryrun
        );

        if ($dryrun) {
            return CommerceNativeFulfillmentResult::skipped(
                $grant,
                'Dry-run: Native course access was validated without Moodle mutation.',
                $payload + ['dryrun' => true]
            );
        }

        return CommerceNativeFulfillmentResult::completed(
            $grant,
            $payload,
            'Native Moodle course access was granted.'
        );
    }
}
