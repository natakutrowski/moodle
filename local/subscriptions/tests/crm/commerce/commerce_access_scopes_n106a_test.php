<?php

namespace local_subscriptions;

final class commerce_access_scopes_n106a_test extends \advanced_testcase {
    public function test_scope_list_uses_crm_polish_and_aggregated_plan_counts(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/index.php');
        $this->assertStringContainsString('CrmPageHeader::render', $source);
        $this->assertStringContainsString('commerce-scope-list-card', $source);
        $this->assertStringContainsString('GROUP BY accessscopeid', $source);
        $this->assertStringContainsString('commerce_scope_courses_badge', $source);
        $this->assertStringContainsString('commerce_scope_plans_badge', $source);
    }

    public function test_scope_editor_uses_searchable_course_picker_and_dependency_warning(): void {
        $form = file_get_contents(__DIR__ . '/../../../forms/access_scope_form.php');
        $page = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/edit.php');
        $this->assertStringContainsString("addElement('autocomplete', 'course_ids'", $form);
        $this->assertStringNotContainsString("addElement('select', 'course_ids'", $form);
        $this->assertStringContainsString('commerce_scope_dependency_title', $page);
        $this->assertStringContainsString('commerce-scope-form-card', $page);
        $this->assertStringContainsString('commerce_scope_general_title', $page);
    }

    public function test_scope_pages_live_under_products_navigation_and_view_is_polished(): void {
        $index = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/index.php');
        $edit = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/edit.php');
        $view = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/view.php');

        foreach ([$index, $edit, $view] as $source) {
            $this->assertStringContainsString('CommerceSectionNavigationRenderer::PRODUCTS', $source);
            $this->assertStringNotContainsString('CommerceSectionNavigationRenderer::CONFIGURATION', $source);
        }
        $this->assertStringContainsString('$PAGE->set_context($context);', $view);
        $this->assertLessThan(
            strpos($view, '$title = format_string($scope->name);'),
            strpos($view, '$PAGE->set_context($context);')
        );
        $this->assertStringContainsString('CrmPageHeader::render', $view);
        $this->assertStringContainsString('commerce-scope-view-card', $view);
        $this->assertStringContainsString('commerce-scope-technical-card', $view);
    }

    public function test_scope_delete_actions_have_trash_icons(): void {
        $index = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/index.php');
        $view = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/view.php');
        $this->assertStringContainsString('fa fa-trash me-1', $index);
        $this->assertStringContainsString('fa fa-trash me-1', $view);
    }

    public function test_course_journey_hides_native_details_marker(): void {
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertStringContainsString('.crm-products-course-journey > summary::-webkit-details-marker', $styles);
        $this->assertStringContainsString('.crm-products-course-journey > summary::marker', $styles);
    }

    public function test_scope_view_links_courses_and_prefixes_internal_id(): void {
        $view = file_get_contents(__DIR__ . '/../../../admin/commerce/accessscopes/view.php');
        $this->assertStringContainsString("new moodle_url('/course/view.php', ['id' => \$course->id])", $view);
        $this->assertStringContainsString("'#' . (int)\$scope->id", $view);
    }

}
