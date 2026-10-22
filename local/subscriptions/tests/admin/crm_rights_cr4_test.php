<?php
namespace local_subscriptions\admin;
defined('MOODLE_INTERNAL') || die();

/** @covers \local_subscriptions\admin\CrmRoleAssignments */
final class crm_rights_cr4_test extends \advanced_testcase {
    protected function setUp(): void { parent::setUp(); $this->resetAfterTest(); }

    public function test_only_six_known_templates_accepted(): void {
        $this->expectException(\InvalidArgumentException::class);
        CrmRoleAssignments::resolve_role('manager');
    }
    public function test_course_student_role_survives_system_role_assignment_and_removal(): void {
        global $DB;
        $admin = get_admin();
        $target = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $student = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        role_assign($student->id, $target->id, $coursecontext->id);
        $shortname = 'campus_crm_support';
        $role = $DB->get_record('role', ['shortname' => $shortname]);
        if (!$role) {
            $roleid = create_role('CampusFR — Support', $shortname,
                'CampusFR CRM system role (CR.3). No Moodle administration rights.', '');
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        }
        $this->setAdminUser();
        CrmRoleAssignments::change((int)$admin->id, (int)$target->id, $shortname, 'add');
        $this->assertArrayHasKey($shortname, CrmRoleAssignments::assigned((int)$target->id));
        CrmRoleAssignments::change((int)$admin->id, (int)$target->id, $shortname, 'remove');
        $this->assertArrayNotHasKey($shortname, CrmRoleAssignments::assigned((int)$target->id));
        $this->assertTrue(user_has_role_assignment($target->id, $student->id, $coursecontext->id));
    }
    public function test_self_assignment_denied(): void {
        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        CrmRoleAssignments::change((int)get_admin()->id, (int)get_admin()->id, 'campus_crm_support', 'add');
    }
}
