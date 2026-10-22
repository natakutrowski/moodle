<?php

declare(strict_types=1);

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user360\rendering\User360SupportOverviewRenderer;

/** CR.5 UX5: guard against adding a button to the wrong renderer or reverting to a three-column course row. */
final class crm_rights_cr5_ux5_test extends \advanced_testcase {

    public function test_role_tile_is_rendered_in_visible_user360_quick_actions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $PAGE;
        $target = $this->getDataGenerator()->create_user();
        $PAGE->set_url(new \moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $target->id]));

        $method = new \ReflectionMethod(User360SupportOverviewRenderer::class, 'support_actions');
        $html = $method->invoke(null, (object)['user' => $target, 'iscommerceguest' => false]);

        $this->assertStringContainsString('crm-user360-n114b-action-grid', $html);
        $this->assertStringContainsString('/admin/users/crm_roles.php', $html);
        $this->assertStringContainsString('fa fa-shield', $html);
        $this->assertLessThan(
            strpos($html, '/user/profile.php'),
            strpos($html, '/admin/users/crm_roles.php'),
            'CRM roles tile should be placed before the Moodle profile tile.'
        );
    }

    public function test_course_name_is_in_full_width_row_above_progress_details(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $PAGE;
        $PAGE->set_url(new \moodle_url('/local/subscriptions/admin/users/view.php', ['id' => 2]));
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Cours de français avec un intitulé volontairement très long pour tester la mise en page',
            'shortname' => 'CR5UX5LONG',
        ]);
        $progress = (object)[
            'courseid' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'progresspercentage' => 25.0,
            'trackedactivities' => 4,
            'completedactivities' => 1,
            'accessend' => 0,
        ];

        $method = new \ReflectionMethod(User360SupportOverviewRenderer::class, 'learning');
        $html = $method->invoke(null, (object)['learningprogress' => [$progress]]);

        $this->assertStringContainsString('crm-cr5-ux5-course-row d-flex flex-column', $html);
        $this->assertStringContainsString('crm-cr5-course-details d-flex flex-wrap', $html);
        $titlepos = strpos($html, 'Cours de français avec un intitulé');
        $detailpos = strpos($html, 'crm-cr5-course-details');
        $this->assertNotFalse($titlepos);
        $this->assertNotFalse($detailpos);
        $this->assertLessThan($detailpos, $titlepos);
    }

    public function test_role_management_retains_a_safe_readonly_path_for_self_and_admin(): void {
        $source = file_get_contents(__DIR__ . '/../../admin/users/crm_roles.php');
        $this->assertStringContainsString('is_siteadmin($userid)', $source);
        $this->assertStringContainsString("\$readonly && \$_SERVER['REQUEST_METHOD'] === 'POST'", $source);
        $this->assertStringContainsString('crm_team_super_admin_help', $source);
    }

    public function test_two_row_layout_does_not_depend_on_replacing_styles_css(): void {
        $renderer = file_get_contents(__DIR__ . '/../../classes/crm/user360/rendering/User360SupportOverviewRenderer.php');
        $this->assertStringContainsString('d-flex flex-column align-items-stretch', $renderer);
        $this->assertStringContainsString('flex-direction:column', $renderer);
        $this->assertStringContainsString('crm-cr5-course-details d-flex flex-wrap', $renderer);
        $this->assertStringContainsString('white-space: normal; overflow: visible', $renderer);
    }
}
