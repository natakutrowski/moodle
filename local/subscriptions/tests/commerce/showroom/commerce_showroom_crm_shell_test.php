<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Certifies that all interactive Showroom administration pages use the CRM shell.
 */
final class commerce_showroom_crm_shell_test extends \advanced_testcase {
    /**
     * @dataProvider showroom_crm_pages_provider
     */
    public function test_interactive_showroom_admin_page_uses_crm_shell(string $relativepath): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/' . $relativepath
        );

        self::assertIsString($source);
        self::assertStringContainsString('CrmPageConfigurator::configure(', $source);
        self::assertStringContainsString(
            'CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS',
            $source
        );
        self::assertStringContainsString('CrmWorkspaceRenderer::end()', $source);
        self::assertStringNotContainsString("set_pagelayout('admin')", $source);
    }

    public static function showroom_crm_pages_provider(): array {
        return [
            'list' => ['index.php'],
            'builder' => ['edit.php'],
            'import' => ['import.php'],
            'history' => ['history.php'],
            'portable export preflight' => ['export_portable_preflight.php'],
        ];
    }

    /**
     * @dataProvider showroom_endpoint_provider
     */
    public function test_non_ui_showroom_endpoint_is_not_forced_into_crm_shell(string $relativepath): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/' . $relativepath
        );

        self::assertIsString($source);
        self::assertStringNotContainsString('CrmWorkspaceRenderer::start(', $source);
    }

    public static function showroom_endpoint_provider(): array {
        return [
            'ajax' => ['ajax.php'],
            'json export' => ['export.php'],
            'portable export stream' => ['export_portable.php'],
            'price preview endpoint' => ['preview_prices.php'],
        ];
    }
}
