<?php
// CR.7 — Discreet display of the current user's CRM roles in the CRM topbar.
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_system;
use local_subscriptions\crm\layout\CrmTopBarRenderer;

final class crm_rights_cr7_my_roles_header_test extends \advanced_testcase {
    /** Find a CRM role or create one in this isolated PHPUnit database. */
    private function crm_role(string $shortname): int {
        global $DB;
        $role = $DB->get_record('role', ['shortname' => $shortname]);
        if ($role) {
            return (int)$role->id;
        }
        $definition = CrmRoleTemplates::definitions()[$shortname];
        $roleid = create_role($definition['name'], $shortname, 'CR7 topbar test');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        return (int)$roleid;
    }

    public function test_course_roles_are_not_displayed_as_crm_roles(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        global $DB;
        $studentroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        role_assign($studentroleid, $user->id, context_course::instance($course->id)->id);
        $this->assertSame(['compact' => '', 'full' => ''],
            CrmTopBarRenderer::crm_role_labels((int)$user->id));
    }

    public function test_multi_role_is_compact_and_exposes_full_role_names(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        foreach (['campus_crm_support', 'campus_crm_content'] as $role) {
            role_assign($this->crm_role($role), $user->id, context_system::instance()->id);
        }
        $result = CrmTopBarRenderer::crm_role_labels((int)$user->id);
        $this->assertSame('Support · Content', $result['compact']);
        $this->assertStringContainsString('Support', $result['full']);
        $this->assertStringContainsString('Content', $result['full']);
    }

    public function test_three_roles_use_plus_count(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        foreach (['campus_crm_operations', 'campus_crm_support', 'campus_crm_content'] as $role) {
            role_assign($this->crm_role($role), $user->id, context_system::instance()->id);
        }
        $result = CrmTopBarRenderer::crm_role_labels((int)$user->id);
        $this->assertSame('Operations · Support +1', $result['compact']);
        $this->assertCount(3, explode(' · ', $result['full']));
    }

    public function test_super_admin_is_virtual(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        global $USER;
        $result = CrmTopBarRenderer::crm_role_labels((int)$USER->id);
        $this->assertSame('Super Admin', $result['compact']);
        $this->assertSame(get_string('crm_team_super_admin', 'local_subscriptions'), $result['full']);
    }

    public function test_header_remains_small_and_read_only(): void {
        $src = file_get_contents(__DIR__ . '/../../classes/crm/layout/CrmTopBarRenderer.php');
        $this->assertIsString($src);
        $this->assertStringContainsString('crm-app-topbar-user-role d-none d-sm-block', $src);
        $this->assertStringContainsString('font-size:.69rem;', $src);
        $this->assertStringNotContainsString('role_assign(', $src);
        $this->assertStringNotContainsString('role_unassign(', $src);
    }
}
