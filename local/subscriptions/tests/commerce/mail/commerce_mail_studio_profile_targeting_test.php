<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\audience\CommerceMarketingProfileAudienceService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingRepeatSuppression;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\library\CommerceMailStudioPreviewService;

final class commerce_mail_studio_profile_targeting_test extends \advanced_testcase {
    private function assign_course_role(\stdClass $user, \stdClass $course, string $role): void {
        global $DB;
        $record = $DB->get_record('role', ['shortname' => $role]);
        if (!$record) {
            $roleid = (int)$DB->insert_record('role', (object)[
                'name' => 'Test ' . $role, 'shortname' => $role, 'description' => '',
                'archetype' => '', 'sortorder' => 200,
            ]);
        } else {
            $roleid = (int)$record->id;
        }
        role_assign($roleid, (int)$user->id, \context_course::instance((int)$course->id)->id);
    }

    private function product(string $sku): int {
        global $DB;
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku, 'type' => 'digital', 'status' => 'active',
            'name' => $sku, 'timecreated' => time(), 'timemodified' => time(),
        ]);
    }

    private function purchase(\stdClass $user, string $itemreference, bool $native = true,
            bool $linked = true, string $status = 'fulfilled'): void {
        global $DB;
        $purchaseid = (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5(random_string(18)),
            'reference' => 'ms-' . random_string(18),
            'type' => $native ? 'digital' : 'subscription',
            'userid' => $linked ? $user->id : null, 'customeremail' => $user->email,
            'status' => $status, 'currency' => 'EUR',
            'subtotalminor' => 100, 'discountminor' => 0, 'totalminor' => 100,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid, 'position' => 0, 'itemtype' => $native ? 'digital' : 'subscription',
            'itemreference' => $itemreference, 'label' => 'Fixture',
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 100,
            'grossminor' => 100, 'discountminor' => 0, 'netminor' => 100,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
    }

    public function test_global_segments_distinguish_students_native_buyers_and_trial_only(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user(['email' => 'ms-student@example.test']);
        $buyer = $this->getDataGenerator()->create_user(['email' => 'ms-buyer@example.test']);
        $trialonly = $this->getDataGenerator()->create_user(['email' => 'ms-trial@example.test']);
        $mixed = $this->getDataGenerator()->create_user(['email' => 'ms-mixed@example.test']);
        $trialpurchaser = $this->getDataGenerator()->create_user(['email' => 'ms-trialbuyer@example.test']);
        $legacybuyer = $this->getDataGenerator()->create_user(['email' => 'ms-legacybuyer@example.test']);
        $guestbuyer = $this->getDataGenerator()->create_user(['email' => 'ms-guestbuyer@example.test']);
        $this->assign_course_role($student, $course1, 'student');
        $this->assign_course_role($trialonly, $course1, 'trialstudent');
        $this->assign_course_role($mixed, $course1, 'trialstudent');
        $this->assign_course_role($mixed, $course2, 'student');
        $this->assign_course_role($trialpurchaser, $course1, 'trialstudent');
        $this->assign_course_role($legacybuyer, $course1, 'trialstudent');
        $this->product('MS-DIGITAL-A');
        $this->purchase($buyer, 'MS-DIGITAL-A');
        $this->purchase($trialpurchaser, 'MS-DIGITAL-A');
        $this->purchase($legacybuyer, 'SUB.PLAN.999', false);
        $this->purchase($guestbuyer, 'MS-DIGITAL-A', true, false, 'paid');
        $service = new CommerceMarketingProfileAudienceService($DB);
        $customers = $service->preview(CommerceMarketingProfileAudienceService::CUSTOMERS);
        $customerids = array_column($customers['recipients'], 'userid');
        self::assertEqualsCanonicalizing([
            (int)$student->id, (int)$buyer->id, (int)$mixed->id, (int)$trialpurchaser->id,
            (int)$guestbuyer->id,
        ], $customerids);
        $trials = $service->preview(CommerceMarketingProfileAudienceService::TRIAL_ONLY);
        self::assertSame([(int)$trialonly->id], array_column($trials['recipients'], 'userid'));
    }

    public function test_multiselect_course_product_and_prior_template_dispatch_exclusions(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user(['email' => 'ms-excluded-course@example.test']);
        $buyer = $this->getDataGenerator()->create_user(['email' => 'ms-excluded-product@example.test']);
        $contacted = $this->getDataGenerator()->create_user(['email' => 'ms-already-contacted@example.test']);
        $this->assign_course_role($student, $course, 'student');
        $this->assign_course_role($contacted, $course2, 'student');
        $productid = $this->product('MS-DIGITAL-B');
        $this->purchase($buyer, 'MS-DIGITAL-B');
        $template = (new CommerceMailLibraryRepository($DB))->save([
            'name' => 'Template repeat check', 'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], ['fr' => ['subject' => 'Hello', 'bodyhtml' => '<p>Salut !</p>']], (int)$student->id);
        $campaignid = (int)$DB->insert_record('local_subs_mail_campaign', (object)[
            'name' => 'Old sent campaign', 'status' => 'completed', 'templateid' => $template->id,
            'audiencesource' => 'manual', 'timecreated' => time(),
            'timemodified' => time(), 'usermodified' => $student->id,
        ]);
        $mailid = (int)$DB->insert_record('local_subs_commerce_mail', (object)[
            'mailtype' => 'MARKETING_CAMPAIGN', 'status' => 'sent',
            'idempotencykey' => 'ms-test-sent-' . random_string(14),
            'recipientemail' => $contacted->email, 'language' => 'fr', 'contextjson' => '{}',
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('local_subs_mail_campaign_recipient', (object)[
            'campaignid' => $campaignid, 'email' => $contacted->email,
            'firstname' => $contacted->firstname, 'lastname' => $contacted->lastname,
            'language' => 'fr', 'userid' => $contacted->id, 'mailid' => $mailid,
            'status' => 'sent', 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $preview = (new CommerceMarketingProfileAudienceService($DB))->preview(
            CommerceMarketingProfileAudienceService::CUSTOMERS,
            [(int)$course->id], [$productid], (int)$template->id
        );
        self::assertSame(3, $preview['summary']['candidates']);
        self::assertSame(0, $preview['summary']['eligible']);
        self::assertSame(1, $preview['summary']['reason_excluded_existing_course']);
        self::assertSame(1, $preview['summary']['reason_excluded_purchased_product']);
        self::assertSame(1, $preview['summary']['reason_previously_contacted']);
        $retry = (new CommerceMarketingProfileAudienceService($DB))->preview(
            CommerceMarketingProfileAudienceService::CUSTOMERS,
            [], [], (int)$template->id, false
        );
        self::assertCount(3, $retry['recipients']);
        self::assertArrayHasKey(strtolower((string)$contacted->email),
            (new CommerceMarketingRepeatSuppression($DB))->delivered_or_queued_emails((int)$template->id));
    }

    public function test_accounts_without_student_or_trial_roles_are_targetable_and_safely_filtered(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $plain = $this->getDataGenerator()->create_user(['email' => 'ms-noroles@example.test']);
        $nativebuyer = $this->getDataGenerator()->create_user(['email' => 'ms-noroles-native@example.test']);
        $legacybuyer = $this->getDataGenerator()->create_user(['email' => 'ms-noroles-legacy@example.test']);
        $guestbuyer = $this->getDataGenerator()->create_user(['email' => 'ms-noroles-guest@example.test']);
        $student = $this->getDataGenerator()->create_user(['email' => 'ms-noroles-student@example.test']);
        $trial = $this->getDataGenerator()->create_user(['email' => 'ms-noroles-trial@example.test']);
        $suspended = $this->getDataGenerator()->create_user([
            'email' => 'ms-noroles-suspended@example.test', 'suspended' => 1,
        ]);
        $teacher = $this->getDataGenerator()->create_user(['email' => 'ms-noroles-teacher@example.test']);
        $outsidecourse = $this->getDataGenerator()->create_user([
            'email' => 'ms-noroles-system-student@example.test',
        ]);
        $emailstop = $this->getDataGenerator()->create_user([
            'email' => 'ms-noroles-emailstop@example.test', 'emailstop' => 1,
        ]);
        $this->assign_course_role($student, $course, 'student');
        $this->assign_course_role($trial, $course, 'trialstudent');
        $this->assign_course_role($teacher, $course, 'editingteacher');
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        role_assign((int)$studentrole->id, (int)$outsidecourse->id, \context_system::instance()->id);
        $productid = $this->product('MS-NOROLES-A');
        $this->purchase($nativebuyer, 'MS-NOROLES-A');
        $this->purchase($legacybuyer, 'SUB.MS.NOROLES', false);
        $this->purchase($guestbuyer, 'MS-NOROLES-A', true, false, 'paid');
        $service = new CommerceMarketingProfileAudienceService($DB);
        $all = $service->preview(CommerceMarketingProfileAudienceService::NO_STUDENT_TRIAL);
        $ids = array_column($all['recipients'], 'userid');
        foreach ([$plain, $nativebuyer, $legacybuyer, $guestbuyer] as $allowed) {
            self::assertContains((int)$allowed->id, $ids);
        }
        foreach ([$student, $trial, $outsidecourse, $teacher, $suspended, $emailstop] as $blocked) {
            self::assertNotContains((int)$blocked->id, $ids);
        }
        self::assertGreaterThanOrEqual(1, $all['summary']['reason_inactive_account'] ?? 0);
        self::assertGreaterThanOrEqual(1, $all['summary']['reason_staff_role'] ?? 0);
        $prospects = $service->preview(CommerceMarketingProfileAudienceService::NO_STUDENT_TRIAL_NO_PURCHASE);
        $prospectids = array_column($prospects['recipients'], 'userid');
        self::assertContains((int)$plain->id, $prospectids);
        foreach ([$nativebuyer, $legacybuyer, $guestbuyer, $student, $trial, $outsidecourse] as $blocked) {
            self::assertNotContains((int)$blocked->id, $prospectids);
        }
        $excluded = $service->preview(CommerceMarketingProfileAudienceService::NO_STUDENT_TRIAL,
            [], [$productid]);
        self::assertNotContains((int)$nativebuyer->id, array_column($excluded['recipients'], 'userid'));
        self::assertNotContains((int)$guestbuyer->id, array_column($excluded['recipients'], 'userid'));
    }

    public function test_unsaved_preview_uses_actual_body_and_brand_layout(): void {
        $this->resetAfterTest(true);
        $result = CommerceMailStudioPreviewService::render([
            'subject' => 'Bonjour {{firstname}}',
            'preheader' => 'Ce sera une surprise',
            'bodyhtml' => '<p>Salut {{fullname}} !</p>',
        ], 'fr', CommerceMailLibrary::CATEGORY_MARKETING);
        self::assertSame('Bonjour Camille', $result['subject']);
        self::assertStringContainsString('Camille Martin', $result['html']);
        self::assertStringContainsString('Ce sera une surprise', $result['html']);
        self::assertStringNotContainsString('{{fullname}}', $result['html']);
    }

    public function test_rejects_untrusted_exclusion_identifiers(): void {
        self::assertSame([42, 75], CommerceMarketingProfileAudienceService::normalise_ids([42, '75', 42]));
        $this->expectException(\invalid_parameter_exception::class);
        CommerceMarketingProfileAudienceService::normalise_ids(['abc']);
    }
}
