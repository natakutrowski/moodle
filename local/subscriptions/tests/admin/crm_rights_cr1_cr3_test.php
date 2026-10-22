<?php
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

/** @covers \local_subscriptions\admin\CrmRoleTemplates */
final class crm_rights_cr1_cr3_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_templates_contain_only_declared_system_capabilities(): void {
        global $DB;
        $capabilities = $DB->get_records('capabilities', ['component' => 'local_subscriptions'], '', 'name,contextlevel');
        foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
            $this->assertSame($definition['capabilities'], array_unique($definition['capabilities']));
            foreach ($definition['capabilities'] as $capability) {
                $this->assertArrayHasKey($capability, $capabilities, $shortname);
                $this->assertSame((int)CONTEXT_SYSTEM, (int)$capabilities[$capability]->contextlevel);
            }
        }
    }

    public function test_support_cannot_configure_or_manage_roles(): void {
        $caps = CrmRoleTemplates::capabilities('campus_crm_support');
        $this->assertContains(Capabilities::MANAGE_INBOX, $caps);
        $this->assertNotContains(Capabilities::MANAGE_CONFIGURATION, $caps);
        $this->assertNotContains(Capabilities::MANAGE_CRM_ROLES, $caps);
        $this->assertNotContains(Capabilities::MANAGE_PURCHASE_NOTES, $caps);
    }

    public function test_finance_read_only_has_no_purchase_note_write(): void {
        $caps = CrmRoleTemplates::capabilities('campus_crm_finance');
        $this->assertContains(Capabilities::VIEW_PAYMENTS, $caps);
        $this->assertNotContains(Capabilities::MANAGE_PURCHASE_NOTES, $caps);
    }

    public function test_multiple_system_roles_preserve_course_student_role(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $student = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        $systemcontext = \context_system::instance();
        role_assign($student->id, $user->id, $coursecontext->id);
        $support = create_role('CR Support test', 'cr_support_test', '', '');
        $marketing = create_role('CR Marketing test', 'cr_marketing_test', '', '');
        assign_capability(Capabilities::MANAGE_INBOX, CAP_ALLOW, $support, $systemcontext->id);
        assign_capability(Capabilities::MANAGE_SHOWROOMS, CAP_ALLOW, $marketing, $systemcontext->id);
        role_assign($support, $user->id, $systemcontext->id);
        role_assign($marketing, $user->id, $systemcontext->id);
        $this->setUser($user);
        $this->assertTrue(has_capability(Capabilities::MANAGE_INBOX, $systemcontext));
        $this->assertTrue(has_capability(Capabilities::MANAGE_SHOWROOMS, $systemcontext));
        $this->assertTrue(user_has_role_assignment($user->id, $student->id, $coursecontext->id));
        role_unassign($support, $user->id, $systemcontext->id);
        $this->assertFalse(has_capability(Capabilities::MANAGE_INBOX, $systemcontext));
        $this->assertTrue(has_capability(Capabilities::MANAGE_SHOWROOMS, $systemcontext));
        $this->assertTrue(user_has_role_assignment($user->id, $student->id, $coursecontext->id));
    }

    public function test_unknown_template_rejected(): void {
        $this->expectException(\InvalidArgumentException::class);
        CrmRoleTemplates::capabilities('manager');
    }
}
