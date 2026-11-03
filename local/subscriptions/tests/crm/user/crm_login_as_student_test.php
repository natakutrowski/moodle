<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\CrmRoleTemplates;
use local_subscriptions\crm\user\CrmStudentImpersonation;

final class crm_login_as_student_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_support_receives_crm_only_permission_not_native_login_as(): void {
        $support = CrmRoleTemplates::capabilities('campus_crm_support');
        self::assertContains(Capabilities::LOGIN_AS_STUDENT, $support);
        self::assertContains(Capabilities::VIEW_USERS, $support);
        self::assertNotContains('moodle/user:loginas', $support);
        self::assertNotContains(Capabilities::MANAGE_USERS, $support);
    }

    public function test_unenrolled_user_can_be_selected_and_self_cannot(): void {
        $actor = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        self::assertTrue(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
        self::assertFalse(CrmStudentImpersonation::is_eligible_target($target, (int)$target->id));
    }

    public function test_suspended_deleted_and_unconfirmed_users_are_rejected(): void {
        $actor = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $target->suspended = 1;
        self::assertFalse(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
        $target->suspended = 0;
        $target->deleted = 1;
        self::assertFalse(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
        $target->deleted = 0;
        $target->confirmed = 0;
        self::assertFalse(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
    }

    public function test_teacher_can_be_selected_if_not_admin(): void {
        global $DB;
        $actor = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        role_assign($teacherrole->id, $target->id, \context_course::instance($course->id)->id);
        self::assertTrue(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
    }

    public function test_non_admin_crm_staff_can_be_selected(): void {
        $actor = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $role = create_role('Test CRM support', 'test_crm_support_login_as', '', '');
        set_role_contextlevels($role, [CONTEXT_SYSTEM]);
        role_assign($role, $target->id, \context_system::instance()->id);
        self::assertTrue(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
    }

    public function test_site_administrator_is_rejected(): void {
        $actor = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        set_config('siteadmins', (string)$target->id);
        self::assertTrue(is_siteadmin($target->id));
        self::assertFalse(CrmStudentImpersonation::is_eligible_target($target, (int)$actor->id));
    }

    public function test_operator_must_have_crm_capability_not_native_moodle_capability(): void {
        $operator = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $ctx = \context_system::instance();
        $role = create_role('CRM login as operator', 'test_crm_student_login_as_role', '', '');
        set_role_contextlevels($role, [CONTEXT_SYSTEM]);
        assign_capability(Capabilities::VIEW_USERS, CAP_ALLOW, $role, $ctx->id);
        assign_capability(Capabilities::LOGIN_AS_STUDENT, CAP_ALLOW, $role, $ctx->id);
        role_assign($role, $operator->id, $ctx->id);
        $this->setUser($operator);
        self::assertFalse(has_capability('moodle/user:loginas', $ctx));
        self::assertTrue(CrmStudentImpersonation::can_start($student));
    }
}
