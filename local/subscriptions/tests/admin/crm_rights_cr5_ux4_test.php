<?php
namespace local_subscriptions\admin;

/** Static guardrails for CR.5 UX4; run Moodle integration tests separately. */
final class crm_rights_cr5_ux4_test extends \advanced_testcase {
    public function test_admin_team_display_is_virtual(): void {
        $src = file_get_contents(__DIR__ . '/../../admin/team/index.php');
        $this->assertStringContainsString('get_admins()', $src);
        $this->assertStringContainsString('crm_team_super_admin', $src);
        $this->assertStringNotContainsString('role_assign(', $src);
    }

    public function test_course_names_use_moodle_multilang_and_stacked_layout(): void {
        $src = file_get_contents(__DIR__ . '/../../classes/crm/user360/rendering/User360SupportOverviewRenderer.php');
        $this->assertStringContainsString('format_string(', $src);
        $this->assertStringContainsString('context_course::instance(', $src);
        $this->assertStringContainsString('crm-cr5-course-details', $src);
    }

    public function test_pending_payment_status_is_translated(): void {
        $src = file_get_contents(__DIR__ . '/../../classes/crm/user360/rendering/User360SupportOverviewRenderer.php');
        $this->assertStringContainsString("'payment_pending'", $src);
        $this->assertStringContainsString('crm_user360_n114_status_pending', $src);
    }

    public function test_role_action_is_in_quick_actions_only(): void {
        $view = file_get_contents(__DIR__ . '/../../admin/users/view.php');
        $quick = file_get_contents(__DIR__ . '/../../classes/output/UserProfileRenderer.php');
        $this->assertStringNotContainsString('admin/users/crm_roles.php', $view);
        $this->assertStringContainsString('admin/users/crm_roles.php', $quick);
    }
}
