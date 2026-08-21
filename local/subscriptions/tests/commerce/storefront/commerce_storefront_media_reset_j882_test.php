<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Regression contracts for media persistence, public rendering and reset. */
final class commerce_storefront_media_reset_j882_test extends \advanced_testcase {
    public function test_editor_uses_safe_shell_mode_fallback(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'
        );

        $this->assertStringContainsString(
            "\$shellmode = strtolower(trim((string)(\$configuration['shell_mode'] ?? 'standard')));",
            $source
        );
        $this->assertStringNotContainsString(
            "? (string)\$configuration['shell_mode']",
            $source
        );
    }

    public function test_section_save_reloads_persisted_metadata(): void {
        global $CFG;
        $endpoint = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/products/storefront_section_save.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontSectionSaveService.php'
        );

        $this->assertStringContainsString('get_editor_data', $endpoint);
        $this->assertStringContainsString(
            'Storefront section could not be reloaded after save.',
            $endpoint
        );
        $this->assertStringContainsString('find_section_index', $service);
        $this->assertStringContainsString('$sections[$targetindex] = $section;', $service);
        $this->assertStringContainsString("['videosource'] = 'upload'", $service);
        $this->assertStringContainsString(
            'Storefront media item ID changed after persistence.',
            $endpoint
        );
    }

    public function test_public_rich_content_uses_storefront_context(): void {
        global $CFG;
        $presenter = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );

        $this->assertStringContainsString('format_storefront_content', $presenter);
        $this->assertStringContainsString("'context' => \$files->context()", $presenter);
        $this->assertStringContainsString('rewrite_for_display', $presenter);
        $this->assertStringContainsString("'noclean' => true", $presenter);
    }

    public function test_reset_action_removes_configuration_and_owned_files(): void {
        global $CFG;
        $page = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontResetService.php'
        );

        $this->assertStringContainsString("'storefront_action', '', PARAM_ALPHANUMEXT", $page);
        $this->assertStringContainsString('commerce-storefront-reset-dialog', $page);
        $this->assertStringNotContainsString("'class' => 'modal fade'", $page);
        $this->assertStringContainsString('delete_item_area', $service);
        $this->assertStringContainsString("unset(\n            \$metadata['storefront']", $service);
    }
}
