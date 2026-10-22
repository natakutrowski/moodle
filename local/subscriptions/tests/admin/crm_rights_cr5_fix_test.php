<?php
namespace local_subscriptions\admin;

/** Regression checks for CR.5 team UI routes and search safeguards. */
final class crm_rights_cr5_fix_test extends \advanced_testcase {
    public function test_team_pages_load_complete_user_records_for_fullname(): void {
        $root = dirname(__DIR__, 2);
        $index = file_get_contents($root . '/admin/team/index.php');
        $add = file_get_contents($root . '/admin/team/add.php');
        $this->assertStringContainsString('SELECT DISTINCT u.*', $index);
        $this->assertStringContainsString('SELECT * FROM {user}', $add);
    }

    public function test_search_endpoint_has_server_side_access_checks(): void {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root . '/admin/team/search.php');
        $this->assertStringContainsString('Capabilities::MANAGE_CRM_ROLES', $source);
        $this->assertStringContainsString("'moodle/role:assign'", $source);
        $this->assertStringContainsString('require_sesskey()', $source);
    }

    public function test_role_catalog_has_localized_permissions_label(): void {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root . '/admin/team/roles.php');
        $this->assertStringContainsString("get_string('crm_team_permissions', 'local_subscriptions')", $source);
        $this->assertStringNotContainsString("get_string('permissions')", $source);
    }
}
