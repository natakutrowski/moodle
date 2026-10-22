<?php
namespace local_subscriptions\admin;

/** CR.5 UX regression checks. */
final class crm_rights_cr5_ux_test extends \advanced_testcase {
    public function test_role_page_has_bulk_save_and_csrf_protection(): void {
        $source = file_get_contents(__DIR__ . '/../../admin/users/crm_roles.php');
        $this->assertStringContainsString("name' => 'roles[]'", $source);
        $this->assertStringContainsString('require_sesskey()', $source);
        $this->assertStringContainsString('CrmRoleAssignments::change(', $source);
        $this->assertStringContainsString('format_string($assignment->fullname', $source);
    }
    public function test_team_uses_cards_and_roles_show_live_permissions(): void {
        $members = file_get_contents(__DIR__ . '/../../admin/team/index.php');
        $roles = file_get_contents(__DIR__ . '/../../admin/team/roles.php');
        $this->assertStringContainsString('card h-100', $members);
        $this->assertStringContainsString("get_records('role_capabilities'", $roles);
        $this->assertStringContainsString("has_capability('moodle/role:manage'", $roles);
    }
}
