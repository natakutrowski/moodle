<?php
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

/** CR.5 navigation and rights integration tests. */
final class crm_rights_cr5_test extends \advanced_testcase {
    public function test_team_navigation_key_registered(): void {
        $this->assertTrue(\local_subscriptions\crm\navigation\CrmNavigationKeys::is_valid('team'));
    }

    public function test_non_privileged_user_cannot_see_team_navigation(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $items = (new \local_subscriptions\crm\navigation\CrmNavigationRegistry())->visible_items();
        $keys = array_map(static fn($item) => $item->key, $items);
        $this->assertNotContains('team', $keys);
    }

    public function test_roles_never_include_role_administration_capability(): void {
        foreach (CrmRoleTemplates::definitions() as $template) {
            $this->assertNotContains(Capabilities::MANAGE_CRM_ROLES, $template['capabilities']);
        }
    }

    public function test_templates_are_unique(): void {
        foreach (CrmRoleTemplates::definitions() as $template) {
            $this->assertSame(array_values(array_unique($template['capabilities'])), $template['capabilities']);
        }
    }
}
