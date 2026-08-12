<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Certifies CRM navigation chrome on interactive Showroom administration pages.
 */
final class commerce_showroom_crm_navigation_test extends \advanced_testcase {
    /**
     * @dataProvider interactive_pages_provider
     */
    public function test_interactive_page_has_crm_breadcrumb(
        string $relativepath
    ): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/showrooms/'
                . $relativepath
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS',
            $source
        );
        self::assertStringContainsString(
            'CrmBreadcrumbRenderer::render([',
            $source
        );
        self::assertStringContainsString(
            "get_string('commerce_showroom_cms_title', 'local_subscriptions')",
            $source
        );
    }

    /**
     * @dataProvider standard_pages_provider
     */
    public function test_standard_page_has_crm_page_header(
        string $relativepath
    ): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/showrooms/'
                . $relativepath
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'CrmPageHeader::render(',
            $source
        );
        self::assertStringContainsString(
            'HelpContext::COMMERCE',
            $source
        );
    }

    public static function interactive_pages_provider(): array {
        return [
            'list' => ['index.php'],
            'builder' => ['edit.php'],
            'import' => ['import.php'],
            'history' => ['history.php'],
            'portable export preflight' => ['export_portable_preflight.php'],
        ];
    }

    public static function standard_pages_provider(): array {
        return [
            'list' => ['index.php'],
            'import' => ['import.php'],
            'history' => ['history.php'],
            'portable export preflight' => ['export_portable_preflight.php'],
        ];
    }
}
