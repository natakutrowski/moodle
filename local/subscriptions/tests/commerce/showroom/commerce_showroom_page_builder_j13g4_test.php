<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomPageTemplateRegistry;

final class commerce_showroom_page_builder_j13g4_test extends \advanced_testcase {
    public function test_page_templates_cover_main_sales_scenarios(): void {
        $templates = CommerceShowroomPageTemplateRegistry::definitions();
        foreach (['launch', 'digital', 'course', 'bundle'] as $key) {
            self::assertArrayHasKey($key, $templates);
            self::assertNotEmpty($templates[$key]['blocks']);
        }
    }

    public function test_admin_exposes_import_export_and_templates(): void {
        global $CFG;
        $edit = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php');
        $ajax = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/ajax.php');
        self::assertIsString($edit);
        self::assertIsString($ajax);
        self::assertStringContainsString('CommerceShowroomPageTemplateRegistry::definitions()', $edit);
        self::assertStringContainsString("case 'applytemplate'", $ajax);
        self::assertFileExists($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/export.php');
        self::assertFileExists($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/import.php');
    }
}
