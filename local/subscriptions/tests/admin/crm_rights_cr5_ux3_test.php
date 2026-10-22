<?php
namespace local_subscriptions\admin;

/** Regression checks for the UX3 rights and course progress patch. */
final class crm_rights_cr5_ux3_test extends \advanced_testcase {
    public function test_course_roles_use_native_manual_assignments_and_sesskey(): void {
        $source = file_get_contents(__DIR__ . '/../../admin/users/crm_roles.php');
        $this->assertStringContainsString("$" . "assignment->component !== ''", $source);
        $this->assertStringContainsString('require_capability(\'moodle/role:assign\', $coursecontext)', $source);
        $this->assertStringContainsString('require_sesskey()', $source);
        $this->assertStringContainsString('role_assign($newroleid, $userid, $coursecontext->id)', $source);
        $this->assertStringContainsString("role_unassign($" . "existingrole, $" . "userid, $" . "coursecontext->id, '', 0)", $source);
    }

    public function test_latest_completion_sql_has_unique_first_column(): void {
        $source = file_get_contents(__DIR__ . '/../../classes/crm/success/repositories/MoodleCourseProgressRepository.php');
        $this->assertStringContainsString('SELECT cmc.id AS completionid,', $source);
        $this->assertStringContainsString('cm.course AS courseid,', $source);
    }
}
