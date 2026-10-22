<?php
// CampusFR CR.6 — dynamic capability certification and endpoint security regression tests.
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_system;
use local_subscriptions\crm\navigation\CrmNavigationRegistry;

/**
 * @covers \local_subscriptions\admin\CrmRoleTemplates
 * @covers \local_subscriptions\admin\CrmRoleAssignments
 */
final class crm_rights_cr6_certification_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Read capability NAMES, not the primary keys from get_records('capabilities').
     * has_capability() only accepts strings such as local/subscriptions:view_dashboard.
     *
     * @return string[]
     */
    private function crm_capability_names(): array {
        global $DB;
        return $DB->get_fieldset_select('capabilities', 'name',
            'component = :component', ['component' => 'local_subscriptions']);
    }

    /**
     * Regression guard: the certification must actually evaluate capability names.
     */
    public function test_certification_inventory_contains_real_capability_names(): void {
        $names = $this->crm_capability_names();
        $this->assertNotEmpty($names);
        $this->assertContains(Capabilities::VIEW_DASHBOARD, $names);
        $this->assertContains(Capabilities::MANAGE_CRM_ROLES, $names);
        $this->assertContains(Capabilities::MANAGE_SHOWROOMS, $names);
        foreach ($names as $name) {
            $this->assertIsString($name);
            $this->assertStringStartsWith('local/subscriptions:', $name);
        }
    }

    /**
     * Verify the exact role context restriction using the same Moodle DB API as the audit CLI.
     */
    public function test_system_role_context_levels_are_read_as_values(): void {
        global $DB;
        $roleid = $this->role_from_template('campus_crm_support');
        $levels = array_map('intval', $DB->get_fieldset_select(
            'role_context_levels', 'contextlevel', 'roleid = :roleid', ['roleid' => $roleid]
        ));
        $this->assertSame([(int)CONTEXT_SYSTEM], $levels);
    }

    /**
     * Creates an isolated system role with the CR.3 template's exact capabilities.
     * Does not depend on roles previously installed on DEV or alter real users.
     */
    private function role_from_template(string $shortname): int {
        $definition = CrmRoleTemplates::definitions()[$shortname];
        $roleid = create_role($definition['name'], 'cr6test_' . $shortname,
            'Test only — CR6 role, removed by resetAfterTest.', '');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        $contextid = context_system::instance()->id;
        foreach ($definition['capabilities'] as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $contextid, true);
        }
        return $roleid;
    }

    public function test_six_profiles_have_exact_effective_capabilities(): void {
        $context = context_system::instance();
        $all = $this->crm_capability_names();
        $this->assertNotEmpty($all);
        $this->assertCount(6, CrmRoleTemplates::definitions());
        foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
            $userid = $this->getDataGenerator()->create_user()->id;
            $roleid = $this->role_from_template($shortname);
            role_assign($roleid, $userid, $context->id);
            $this->setUser($userid);
            foreach ($all as $capability) {
                $expected = in_array($capability, $definition['capabilities'], true);
                $this->assertSame($expected, has_capability($capability, $context),
                    $shortname . ': ' . $capability);
            }
            foreach (['moodle/site:config', 'moodle/role:assign', 'moodle/role:manage'] as $sensitive) {
                $this->assertFalse(has_capability($sensitive, $context), $shortname . ': ' . $sensitive);
            }
        }
    }

    public function test_student_only_cannot_access_crm(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $student = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        role_assign($student->id, $user->id, context_course::instance($course->id)->id);
        $this->setUser($user);
        $context = context_system::instance();
        foreach ($this->crm_capability_names() as $capability) {
            $this->assertFalse(has_capability($capability, $context), $capability);
        }
    }

    public function test_multi_role_union_then_partial_removal_preserves_student(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);
        $student = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        role_assign($student->id, $user->id, $coursecontext->id);
        $system = context_system::instance();
        $support = $this->role_from_template('campus_crm_support');
        $content = $this->role_from_template('campus_crm_content');
        role_assign($support, $user->id, $system->id);
        role_assign($content, $user->id, $system->id);
        $this->setUser($user);
        $this->assertTrue(has_capability(Capabilities::MANAGE_INBOX, $system));
        $this->assertTrue(has_capability(Capabilities::MANAGE_SHOWROOMS, $system));
        $this->assertFalse(has_capability(Capabilities::MANAGE_CONFIGURATION, $system));
        $this->assertFalse(has_capability(Capabilities::MANAGE_CRM_ROLES, $system));
        role_unassign($support, $user->id, $system->id);
        $this->setUser($user);
        $this->assertFalse(has_capability(Capabilities::MANAGE_INBOX, $system));
        $this->assertTrue(has_capability(Capabilities::MANAGE_SHOWROOMS, $system));
        $this->assertTrue(user_has_role_assignment($user->id, $student->id, $coursecontext->id));
    }

    public function test_admin_is_virtual_super_admin_without_any_crm_assignment(): void {
        $this->setAdminUser();
        $admin = get_admin();
        $this->assertTrue(is_siteadmin($admin->id));
        foreach ($this->crm_capability_names() as $capability) {
            $this->assertTrue(has_capability($capability, context_system::instance()), $capability);
        }
        // This is a virtual administrative status, not an automatic system CRM role.
        $this->assertEmpty(CrmRoleAssignments::assigned((int)$admin->id));
    }

    public function test_rejects_actor_id_spoof_even_with_granted_delegation_capabilities(): void {
        $user = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $roleid = create_role('CR6 delegated', 'cr6delegated', '', '');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        $system = context_system::instance();
        assign_capability(Capabilities::MANAGE_CRM_ROLES, CAP_ALLOW, $roleid, $system->id, true);
        assign_capability('moodle/role:assign', CAP_ALLOW, $roleid, $system->id, true);
        role_assign($roleid, $user->id, $system->id);
        $this->setUser($user);
        $this->expectException(\moodle_exception::class);
        CrmRoleAssignments::change((int)get_admin()->id, (int)$target->id, 'campus_crm_support', 'add');
    }

    public function test_navigation_destinations_use_their_actual_capabilities(): void {
        $this->setAdminUser();
        $items = (new CrmNavigationRegistry())->visible_items();
        $expected = [
            '/admin/inbox/diagnostics.php' => Capabilities::MANAGE_CONFIGURATION,
            '/admin/work/teams.php' => Capabilities::MANAGE_WORK_CONFIGURATION,
            '/admin/help/diagnostics.php' => Capabilities::MANAGE_CONFIGURATION,
        ];
        $found = [];
        foreach ($items as $item) {
            foreach ($item->children as $child) {
                $url = $child['url']->out(false);
                foreach ($expected as $path => $capability) {
                    if (str_contains($url, $path)) {
                        $this->assertSame($capability, $child['capability'], $path);
                        $found[$path] = true;
                    }
                }
            }
        }
        $this->assertCount(count($expected), $found, 'Navigation destinations not found');
    }

    public function test_administrative_php_entrypoints_have_server_authorisation_guards(): void {
        // Baseline scan: a security guard must exist in each directly executable admin script.
        $root = __DIR__ . '/../../admin';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $checked = 0;
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            // Includes and pure helper libraries are not entrypoints.
            if (!str_contains(substr($source, 0, 2000), 'config.php')) {
                continue;
            }
            $checked++;
            $this->assertMatchesRegularExpression(
                '/(?:AdminSecurity::require|require_capability|require_login)\s*\(/',
                $source, 'Missing server-side access gate: ' . $file->getPathname());
        }
        $this->assertGreaterThan(100, $checked, 'Unexpectedly small endpoint inventory');
    }

    public function test_role_management_post_actions_are_explicit_and_csrf_protected(): void {
        $source = file_get_contents(__DIR__ . '/../../admin/users/crm_roles.php');
        $this->assertStringContainsString("in_array(\$postaction, ['save', 'course'], true)", $source);
        $this->assertStringContainsString("if (\$postaction === 'save')", $source);
        $this->assertStringContainsString("if (\$postaction === 'course')", $source);
        $this->assertStringContainsString('require_sesskey();', $source);
        $this->assertStringContainsString('require_capability(\'moodle/role:assign\', $coursecontext)', $source);
    }
}
