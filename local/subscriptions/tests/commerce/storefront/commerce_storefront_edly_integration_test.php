<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_edly_integration_test extends \advanced_testcase {
    public function test_storefront_supports_native_edly_shell_and_hero_commerce(): void {
        global $CFG;
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/storefront_product.php');
        $contract = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontLayoutContract.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_templates/course.mustache');
        $this->assertStringContainsString('moodle_page_layout', $page);
        $this->assertStringContainsString("'storefront_fullwidth'", $contract);
        $this->assertStringContainsString("HERO_INTEGRATED", $contract);
        $this->assertStringContainsString('{{#commerceinhero}}', $template);
    }

    public function test_storefront_shell_closes_the_moodle_output_lifecycle(): void {
        global $CFG;

        $shell = file_get_contents(
            $CFG->dirroot . '/theme/edly/templates/storefront_shell.mustache'
        );

        $this->assertStringContainsString(
            '{{{ output.standard_end_of_body_html }}}',
            $shell
        );
        $this->assertStringContainsString('</body>', $shell);
        $this->assertStringContainsString('</html>', $shell);
    }

    public function test_admin_global_layout_uses_vertical_fields(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );

        $this->assertStringContainsString(
            'commerce-storefront-admin-layout__fields',
            $page
        );
        $this->assertStringContainsString(
            'commerce-storefront-admin-layout__visibility',
            $page
        );
        $this->assertStringNotContainsString(
            "'col-xl-4 d-flex flex-column justify-content-center gap-2'",
            $page
        );
    }


    public function test_standard_template_renders_integrated_commerce_once(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/product_templates/standard.mustache'
        );

        $this->assertSame(
            1,
            substr_count($template, '{{#commerceinhero}}')
        );
        $this->assertSame(
            1,
            substr_count(
                $template,
                '{{> local_subscriptions/storefront/product_commerce_panel }}'
            ) - substr_count($template, '{{#commerceissidebar}}')
              - substr_count($template, '{{#commercebelowhero}}')
              - substr_count($template, '{{#commerceafterintro}}')
              - substr_count($template, '{{#commercepagebottom}}')
        );
    }

    public function test_admin_layout_visibility_uses_plugin_string(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );

        $this->assertStringContainsString(
            "'commerce_storefront_layout_visibility'",
            $page
        );
        $this->assertStringNotContainsString(
            "get_string('visibility')",
            $page
        );
    }

}
