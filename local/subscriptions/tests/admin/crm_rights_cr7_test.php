<?php
// CampusFR CR.7 - plan validation and safe test account assignment constraints.
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

/** @covers \local_subscriptions\admin\CrmRoleTestPlan */
final class crm_rights_cr7_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    private function sample(array $roles = ['campus_crm_support']): string {
        return json_encode(['version' => 1, 'accounts' => [
            ['id' => 42, 'username' => 'cr7_test_support', 'roles' => $roles],
        ]], JSON_THROW_ON_ERROR);
    }

    public function test_parses_explicit_existing_account_selection(): void {
        $plan = CrmRoleTestPlan::parse($this->sample());
        $this->assertSame(42, $plan[0]['id']);
        $this->assertSame('cr7_test_support', $plan[0]['username']);
        $this->assertSame(['campus_crm_support'], $plan[0]['roles']);
    }

    public function test_supports_student_only_baseline_with_no_crm_role(): void {
        $plan = CrmRoleTestPlan::parse($this->sample([]));
        $this->assertSame([], $plan[0]['roles']);
    }

    public function test_two_roles_are_sorted_for_stable_comparison(): void {
        $plan = CrmRoleTestPlan::parse($this->sample([
            'campus_crm_support', 'campus_crm_content',
        ]));
        $this->assertSame(['campus_crm_content', 'campus_crm_support'], $plan[0]['roles']);
    }

    public function test_rejects_any_non_campus_crm_role(): void {
        $this->expectException(\InvalidArgumentException::class);
        CrmRoleTestPlan::parse($this->sample(['manager']));
    }

    public function test_rejects_duplicate_users(): void {
        $payload = json_decode($this->sample(), true);
        $payload['accounts'][] = $payload['accounts'][0];
        $this->expectException(\InvalidArgumentException::class);
        CrmRoleTestPlan::parse(json_encode($payload));
    }

    public function test_rejects_duplicate_role_names(): void {
        $this->expectException(\InvalidArgumentException::class);
        CrmRoleTestPlan::parse($this->sample(['campus_crm_support', 'campus_crm_support']));
    }

    public function test_rejects_plan_without_identifiable_username(): void {
        $payload = json_decode($this->sample(), true);
        $payload['accounts'][0]['username'] = '';
        $this->expectException(\InvalidArgumentException::class);
        CrmRoleTestPlan::parse(json_encode($payload));
    }

    public function test_diff_only_changes_roles_that_need_changing(): void {
        $diff = CrmRoleTestPlan::diff(
            ['campus_crm_content', 'campus_crm_support'],
            ['campus_crm_content', 'campus_crm_finance']);
        $this->assertSame(['campus_crm_finance'], $diff['add']);
        $this->assertSame(['campus_crm_support'], $diff['remove']);
    }

    public function test_moodle_course_student_role_remains_independent_of_crm_system_roles(): void {
        global $DB;
        $studentuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $systemcontext = \context_system::instance();
        $student = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        role_assign($student->id, $studentuser->id, $coursecontext->id);
        $testroleid = create_role('CR7 Support', 'cr7_support_test_role', '', '');
        set_role_contextlevels($testroleid, [CONTEXT_SYSTEM]);
        assign_capability(Capabilities::VIEW_INBOX, CAP_ALLOW, $testroleid, $systemcontext->id, true);
        role_assign($testroleid, $studentuser->id, $systemcontext->id);
        $this->setUser($studentuser);
        $this->assertTrue(has_capability(Capabilities::VIEW_INBOX, $systemcontext));
        role_unassign($testroleid, $studentuser->id, $systemcontext->id);
        $this->setUser($studentuser);
        $this->assertFalse(has_capability(Capabilities::VIEW_INBOX, $systemcontext));
        $this->assertTrue(user_has_role_assignment($studentuser->id, $student->id, $coursecontext->id));
    }

    public function test_cli_requires_exact_site_confirmation_and_preserves_snapshots(): void {
        $script = file_get_contents(__DIR__ . '/../../cli/crm/test_accounts.php');
        $this->assertStringContainsString("\$opts['confirm'] !== 'YES-CR7'", $script);
        $this->assertStringContainsString("\$CFG->wwwroot", $script);
        $this->assertStringContainsString('is_siteadmin($user->id)', $script);
        $this->assertStringContainsString('BASELINE SNAPSHOT SAVED BEFORE CHANGES', $script);
        $this->assertStringContainsString('cr7_apply_roles(', $script);
    }
}
