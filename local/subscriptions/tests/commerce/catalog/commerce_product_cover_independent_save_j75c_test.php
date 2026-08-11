<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_cover_independent_save_j75c_test
        extends \advanced_testcase {

    public function test_each_visual_format_has_an_independent_save_action(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'assets.php'
        );

        $this->assertStringContainsString(
            "'save_cover_' . \$role",
            $source
        );
        $this->assertStringContainsString(
            "str_starts_with(\$action, 'save_cover_')",
            $source
        );
        $this->assertStringContainsString(
            "'cover_' . \$role",
            $source
        );
        $this->assertStringContainsString(
            'commerce_product_visual_save_format',
            $source
        );
    }

    public function test_global_cover_save_loop_is_removed(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'assets.php'
        );

        $this->assertStringNotContainsString(
            "'save_covers'",
            $source
        );
        $this->assertStringNotContainsString(
            "foreach (\$coverroles as \$role) {\n"
                . "                \$media->store_uploaded_file",
            $source
        );
    }

    public function test_one_save_only_targets_the_selected_role(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'assets.php'
        );

        $this->assertStringContainsString(
            "\$media->store_uploaded_file(\n"
                . "                \$productid,\n"
                . "                \$role,\n"
                . "                'cover_' . \$role",
            $source
        );
    }
}
