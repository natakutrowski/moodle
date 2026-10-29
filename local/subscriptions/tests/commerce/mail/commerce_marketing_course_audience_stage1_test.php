<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\audience\CommerceMarketingCourseAudienceService;

#[\PHPUnit\Framework\Attributes\CoversClass(CommerceMarketingCourseAudienceService::class)]
final class commerce_marketing_course_audience_stage1_test extends \advanced_testcase {
    /** Creates a fulfilled plan-backed purchase for an enrolled test user. */
    private function add_verified_purchase(int $courseid, \stdClass $user): void {
        global $DB;
        $now = time();
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Mail Studio proof fixture ' . random_string(5),
            'duration_key' => '1month', 'is_active' => 1,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $DB->insert_record('subscription_plan_entitlement', (object)[
            'planid' => $planid, 'courseid' => $courseid, 'accesslevel' => 'full',
            'roleshortname' => 'student', 'priority' => 100,
            'timecreated' => $now, 'lastupdate' => $now,
        ]);
        $id = $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5('mailstudio-fixture-' . random_string(12)),
            'reference' => 'ms-proof-' . random_string(12),
            'type' => 'subscription', 'userid' => (int)$user->id,
            'customeremail' => (string)$user->email,
            'status' => 'fulfilled', 'currency' => 'EUR',
            'subtotalminor' => 100, 'discountminor' => 0, 'totalminor' => 100,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $id, 'position' => 0, 'itemtype' => 'subscription',
            'itemreference' => 'SUB.PLAN.' . $planid, 'label' => 'Verified course purchase',
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 100,
            'grossminor' => 100, 'discountminor' => 0, 'netminor' => 100,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
    }

    private function preview(int $courseid, array $skus = []): array {
        global $DB;
        return (new CommerceMarketingCourseAudienceService($DB))->preview($courseid, $skus);
    }

    private function find(array $report, int $userid): array {
        foreach ($report['rows'] as $row) {
            if ($row['userid'] === $userid) {
                return $row;
            }
        }
        self::fail('Candidate user missing from audience report: ' . $userid);
    }

    public function test_full_trial_and_grammar_roles_are_included_without_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $full = $this->getDataGenerator()->create_user();
        $trial = $this->getDataGenerator()->create_user();
        $grammar = $this->getDataGenerator()->create_user();
        foreach (['trialstudent', 'grammarstudent'] as $shortname) {
            if (!$DB->record_exists('role', ['shortname' => $shortname])) {
                create_role($shortname, $shortname, 'Test ' . $shortname);
            }
        }
        $this->getDataGenerator()->enrol_user($full->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $full);
        $this->getDataGenerator()->enrol_user($trial->id, $course->id, 'trialstudent');
        $this->getDataGenerator()->enrol_user($grammar->id, $course->id, 'grammarstudent');
        $report = $this->preview((int)$course->id);
        self::assertSame(3, $report['summary']['candidates']);
        self::assertSame(3, $report['summary']['eligible']);
        self::assertSame(1, $report['summary']['source_student']);
        self::assertSame(1, $report['summary']['source_trialstudent']);
        self::assertSame(1, $report['summary']['source_grammarstudent']);
        self::assertTrue($this->find($report, (int)$full->id)['eligible']);
        self::assertTrue($this->find($report, (int)$trial->id)['eligible']);
        self::assertTrue($this->find($report, (int)$grammar->id)['eligible']);
    }

    public function test_site_administrator_with_student_enrolment_is_excluded(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $admins = \get_admins();
        self::assertNotEmpty($admins);
        $admin = reset($admins);
        $this->getDataGenerator()->enrol_user($admin->id, $course->id, 'student');
        $report = $this->preview((int)$course->id);
        $row = $this->find($report, (int)$admin->id);
        self::assertFalse($row['eligible']);
        self::assertContains('site_administrator', $row['reasons']);
    }

    public function test_expired_enrolment_can_be_filtered_and_suspension_is_always_excluded(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $expired = $this->getDataGenerator()->create_user();
        $suspended = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($expired->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($suspended->id, $course->id, 'student');
        $DB->set_field('user_enrolments', 'timeend', time() - HOURSECS, ['userid' => $expired->id]);
        $DB->set_field('user', 'suspended', 1, ['id' => $suspended->id]);
        $default = $this->preview((int)$course->id);
        self::assertTrue($this->find($default, (int)$expired->id)['eligible']);
        $report = (new CommerceMarketingCourseAudienceService($DB))->preview((int)$course->id, [], null,
            ['exclude_inactive_enrolment' => true]);
        self::assertSame(0, $report['summary']['eligible']);
        self::assertContains('no_active_enrolment', $this->find($report, (int)$expired->id)['reasons']);
        self::assertContains('inactive_account', $this->find($report, (int)$suspended->id)['reasons']);
    }

    public function test_pedagogical_access_and_participation_history_exclude_existing_owners(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $accessuser = $this->getDataGenerator()->create_user();
        $joinuser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($accessuser->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($joinuser->id, $course->id, 'student');
        $now = time();
        $promotionid = $DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'audience-step1-' . random_string(6),
            'name' => 'Test A1', 'courseid' => $course->id,
            'status' => 'draft', 'published' => 0,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => $course->id, 'userid' => $accessuser->id,
            'promotionid' => $promotionid, 'profile' => 'lifetime_full',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'AUD-TEST-' . random_string(8), 'type' => 'course_access',
            'status' => 'active', 'name' => 'Audience fixture',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid, 'courseid' => $course->id, 'userid' => $joinuser->id,
            'productid' => $productid, 'purchasereference' => 'test-audience-ref-' . random_string(5),
            'state' => 'refunded', 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $default = $this->preview((int)$course->id);
        self::assertTrue($this->find($default, (int)$joinuser->id)['eligible']);
        $report = (new CommerceMarketingCourseAudienceService($DB))->preview((int)$course->id, [], null,
            ['exclude_accompaniment' => true]);
        self::assertSame(0, $report['summary']['eligible']);
        self::assertContains('promotion_course_access', $this->find($report, (int)$accessuser->id)['reasons']);
        self::assertContains('pedagogical_participation_history', $this->find($report, (int)$joinuser->id)['reasons']);
    }

    public function test_confirmed_purchase_of_support_sku_excludes_even_without_join(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $buyer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($buyer->id, $course->id, 'student');
        $now = time();
        $purchaseid = $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5('test-audience-' . random_string(10)),
            'reference' => 'cmp-audience-' . random_string(8),
            'type' => 'course_access', 'userid' => $buyer->id,
            'customeremail' => $buyer->email, 'status' => 'completed', 'currency' => 'EUR',
            'subtotalminor' => 28000, 'discountminor' => 0, 'totalminor' => 28000,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid, 'position' => 0, 'itemtype' => 'product',
            'itemreference' => 'A1-FR-SUPPORT', 'label' => 'A1 accompagné',
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 28000, 'grossminor' => 28000,
            'discountminor' => 0, 'netminor' => 28000,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
        $report = (new CommerceMarketingCourseAudienceService($DB))->preview((int)$course->id,
            ['A1-FR-SUPPORT'], null, ['exclude_accompaniment' => true]);
        self::assertSame(0, $report['summary']['eligible']);
        self::assertContains('support_purchase_history', $this->find($report, (int)$buyer->id)['reasons']);
    }

    public function test_grant_for_promotion_join_excludes_current_student(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $now = time();
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'aud-' . random_string(15),
            'idempotencykey' => 'aud-' . random_string(15),
            'purchasereference' => 'cmp-' . random_string(12),
            'itemreference' => 'A1-FR-SUPPORT',
            'productsku' => 'A1-FR-SUPPORT',
            'type' => 'pedagogical_promotion_join',
            'resourcekey' => 'promotion:123:course:' . $course->id . ':product:456',
            'quantity' => 1, 'beneficiaryuserid' => $user->id,
            'beneficiaryemail' => $user->email,
            'validfrom' => $now, 'status' => 'completed',
            'configurationjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $report = (new CommerceMarketingCourseAudienceService($DB))->preview((int)$course->id, [], null,
            ['exclude_accompaniment' => true]);
        self::assertSame(0, $report['summary']['eligible']);
        self::assertContains('promotion_join_grant', $this->find($report, (int)$user->id)['reasons']);
    }

    public function test_duplicate_email_is_excluded_even_if_other_account_is_not_enrolled(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($owner->id, $course->id, 'student');
        $DB->set_field('user', 'email', $owner->email, ['id' => $other->id]);
        $report = $this->preview((int)$course->id);
        self::assertSame(0, $report['summary']['eligible']);
        self::assertContains('ambiguous_email', $this->find($report, (int)$owner->id)['reasons']);
    }

    public function test_purchase_proof_is_optional_but_can_restrict_to_verified_buyers(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $buyer = $this->getDataGenerator()->create_user();
        $unproven = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($buyer->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($unproven->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $buyer);
        $default = $this->preview((int)$course->id);
        self::assertSame(2, $default['summary']['candidates']);
        self::assertSame(2, $default['summary']['eligible']);
        $report = (new CommerceMarketingCourseAudienceService($GLOBALS['DB']))->preview((int)$course->id,
            [], null, ['require_verified_purchase' => true]);
        self::assertSame(1, $report['summary']['eligible']);
        self::assertTrue($this->find($report, (int)$buyer->id)['eligible']);
        self::assertContains('no_verified_course_purchase', $this->find($report, (int)$unproven->id)['reasons']);
    }

    public function test_promotion_offer_sku_is_discovered_automatically(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $user);
        $now = time();
        $promotionid = $DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'aud-offer-' . random_string(7),
            'name' => 'Offer test', 'courseid' => $course->id,
            'status' => 'draft', 'published' => 0,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'TEST-A1-SUPPORT', 'type' => 'course_access',
            'status' => 'active', 'name' => 'Support formula',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_offer', (object)[
            'promotionid' => $promotionid, 'productid' => $productid,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $report = $this->preview((int)$course->id);
        self::assertContains('TEST-A1-SUPPORT', $report['supportskus']);
        self::assertSame(1, $report['summary']['eligible']);
    }

    public function test_trial_filter_excludes_trial_only_and_mixed_roles(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $mixed = $this->getDataGenerator()->create_user();
        $trialonly = $this->getDataGenerator()->create_user();
        if (!$DB->record_exists('role', ['shortname' => 'trialstudent'])) {
            create_role('trialstudent', 'trialstudent', 'Test trial role');
        }
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'trialstudent']);
        $this->getDataGenerator()->enrol_user($mixed->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $mixed);
        role_assign($roleid, $mixed->id, \context_course::instance($course->id)->id);
        $this->getDataGenerator()->enrol_user($trialonly->id, $course->id, 'trialstudent');
        $service = new CommerceMarketingCourseAudienceService($DB);
        $default = $service->preview((int)$course->id);
        self::assertSame(2, $default['summary']['eligible']);
        self::assertTrue($this->find($default, (int)$trialonly->id)['eligible']);
        $filtered = $service->preview((int)$course->id, [], null, ['exclude_trial_role' => true]);
        self::assertSame(0, $filtered['summary']['eligible']);
        self::assertContains('excluded_trial_role', $this->find($filtered, (int)$mixed->id)['reasons']);
        self::assertContains('excluded_trial_role', $this->find($filtered, (int)$trialonly->id)['reasons']);
    }

    public function test_promotion_filter_does_not_require_purchase_unless_requested(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $customer = $this->getDataGenerator()->create_user();
        $unproven = $this->getDataGenerator()->create_user();
        foreach ([$customer, $unproven] as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        }
        $this->add_verified_purchase((int)$course->id, $customer);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => $course->id, 'userid' => $customer->id,
            'promotionid' => null, 'profile' => 'promotion_progressive',
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $service = new CommerceMarketingCourseAudienceService($DB);
        $default = $service->preview((int)$course->id);
        self::assertContains('promotion_course_access', $this->find($default, (int)$customer->id)['reasons']);
        $relaxed = $service->preview((int)$course->id, [], null, ['exclude_promotion_access' => false]);
        self::assertTrue($this->find($relaxed, (int)$customer->id)['eligible']);
        self::assertTrue($this->find($relaxed, (int)$unproven->id)['eligible']);
        $buyers = $service->preview((int)$course->id, [], null,
            ['exclude_promotion_access' => false, 'require_verified_purchase' => true]);
        self::assertContains('no_verified_course_purchase', $this->find($buyers, (int)$unproven->id)['reasons']);
    }

    public function test_grammar_secondary_role_can_be_excluded_explicitly(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $buyer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($buyer->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $buyer);
        if (!$DB->record_exists('role', ['shortname' => 'grammarstudent'])) {
            create_role('grammarstudent', 'grammarstudent', 'Grammar test role');
        }
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'grammarstudent']);
        role_assign($roleid, $buyer->id, \context_course::instance($course->id)->id);
        $service = new CommerceMarketingCourseAudienceService($DB);
        self::assertTrue($this->find($service->preview((int)$course->id), (int)$buyer->id)['eligible']);
        $filtered = $service->preview((int)$course->id, [], null, ['exclude_grammar_role' => true]);
        self::assertContains('excluded_grammar_role', $this->find($filtered, (int)$buyer->id)['reasons']);
    }

    public function test_accompaniment_filter_can_be_enabled_separately_from_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $buyer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($buyer->id, $course->id, 'student');
        $this->add_verified_purchase((int)$course->id, $buyer);
        $now = time();
        $promotionid = $DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'e7-promo-' . random_string(8),
            'name' => 'E7', 'courseid' => $course->id,
            'status' => 'draft', 'published' => 0,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'E7-SUPPORT-' . random_string(6), 'type' => 'course_access',
            'status' => 'active', 'name' => 'Tutoring',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid, 'courseid' => $course->id, 'userid' => $buyer->id,
            'productid' => $productid, 'purchasereference' => 'e7-test-' . random_string(8),
            'state' => 'refunded', 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $service = new CommerceMarketingCourseAudienceService($DB);
        self::assertTrue($this->find($service->preview((int)$course->id), (int)$buyer->id)['eligible']);
        $filtered = $service->preview((int)$course->id, [], null, ['exclude_accompaniment' => true]);
        self::assertContains('pedagogical_participation_history',
            $this->find($filtered, (int)$buyer->id)['reasons']);
    }

    public function test_promotion_only_user_without_course_role_is_in_candidate_list_and_configurable(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => $course->id, 'userid' => $user->id,
            'promotionid' => null, 'profile' => 'promotion_progressive',
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $service = new CommerceMarketingCourseAudienceService($DB);
        $default = $service->preview((int)$course->id);
        self::assertSame(1, $default['summary']['candidates']);
        self::assertSame(1, $default['summary']['source_promotion']);
        self::assertContains('promotion', $this->find($default, (int)$user->id)['sources']);
        self::assertContains('promotion_course_access', $this->find($default, (int)$user->id)['reasons']);
        $included = $service->preview((int)$course->id, [], null, ['exclude_promotion_access' => false]);
        self::assertSame(1, $included['summary']['eligible']);
        self::assertTrue($this->find($included, (int)$user->id)['eligible']);
    }

    public function test_user_with_role_and_promotion_is_counted_once_and_removed_by_promotion_filter(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => $course->id, 'userid' => $user->id,
            'promotionid' => null, 'profile' => 'promotion_progressive',
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $report = $this->preview((int)$course->id);
        self::assertSame(1, $report['summary']['candidates']);
        self::assertSame(1, $report['summary']['source_student']);
        self::assertSame(1, $report['summary']['source_promotion']);
        self::assertSame(['student', 'promotion'], $this->find($report, (int)$user->id)['sources']);
        self::assertFalse($this->find($report, (int)$user->id)['eligible']);
    }

    public function test_student_role_filter_is_configurable_without_full_course_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        self::assertTrue($this->find($this->preview((int)$course->id), (int)$user->id)['eligible']);
        $filtered = (new CommerceMarketingCourseAudienceService($DB))->preview(
            (int)$course->id, [], null, ['exclude_student_role' => true]);
        self::assertContains('excluded_student_role', $this->find($filtered, (int)$user->id)['reasons']);
    }

    public function test_mandatory_emailstop_and_admin_protections_cannot_be_disabled_by_filters(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $DB->set_field('user', 'emailstop', 1, ['id' => $user->id]);
        $report = (new CommerceMarketingCourseAudienceService($DB))->preview((int)$course->id, [], null,
            ['exclude_student_role' => false, 'exclude_trial_role' => false,
             'exclude_promotion_access' => false, 'require_verified_purchase' => false]);
        self::assertContains('email_stopped', $this->find($report, (int)$user->id)['reasons']);
    }
}
