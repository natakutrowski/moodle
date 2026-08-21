<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n118a_legacy_subscription_editor_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_legacy_subscription_editor_is_classified_under_sales(): void {
        $page = $this->file(
            'admin/subscriptions/edit.php'
        );

        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::PURCHASES',
            $page
        );
        self::assertStringNotContainsString(
            'CommerceSectionNavigationRenderer::PRODUCTS',
            $page
        );
        self::assertStringContainsString(
            "'crm_commerce_nav_purchases'",
            $page
        );
    }

    public function test_details_link_is_inside_editor_summary_not_top_back_link(): void {
        $page = $this->file(
            'admin/subscriptions/edit.php'
        );

        self::assertStringContainsString(
            'crm-legacy-subscription-edit-details',
            $page
        );
        self::assertStringContainsString(
            "'subscription_details'",
            $page
        );
        self::assertStringNotContainsString(
            'CrmBackLinkRenderer',
            $page
        );
    }

    public function test_editor_exposes_customer_plan_period_and_status_summary(): void {
        $page = $this->file(
            'admin/subscriptions/edit.php'
        );

        foreach ([
            'crm-legacy-subscription-edit-meta-grid',
            'crm_subscription_edit_current_period',
            'AdminFormatter::subscription_end',
            'admin_user_view_page',
            'commerce_plan_view_page',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $page
            );
        }
    }

    public function test_form_keeps_same_business_fields_with_contextual_help(): void {
        $form = $this->file(
            'classes/form/user_subscription_edit_form.php'
        );

        foreach ([
            "'start_date'",
            "'no_end_date'",
            "'end_date'",
            "'status'",
            'crm_subscription_edit_start_date',
            'crm_subscription_edit_no_end_date',
            'crm_subscription_edit_end_date',
            'crm_subscription_edit_status',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $form
            );
        }
    }
}
