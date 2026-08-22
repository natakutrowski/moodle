<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Regression coverage for CRM Inbox submenu shortcuts added in 7.95O16.3.7. */
final class commerce_795o16_3_7_inbox_crm_nav_shortcuts_test extends \advanced_testcase {
    public function test_inbox_crm_menu_exposes_compose_drafts_and_templates_shortcuts(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );

        self::assertNotFalse($source);

        $inbox = strpos($source, 'key: CrmNavigationKeys::INBOX');
        self::assertIsInt($inbox);
        $work = strpos($source, 'key: CrmNavigationKeys::WORK', $inbox);
        self::assertIsInt($work);
        $block = substr($source, $inbox, $work - $inbox);

        self::assertStringContainsString("crm_inbox_o15_nav_compose", $block);
        self::assertStringContainsString('admin_inbox_compose_page()', $block);
        self::assertStringContainsString("'fa-pencil'", $block);

        self::assertStringContainsString("crm_inbox_o15_nav_drafts", $block);
        self::assertStringContainsString('admin_inbox_drafts_page()', $block);
        self::assertStringContainsString("'fa-file-text-o'", $block);

        self::assertStringContainsString("crm_inbox_o15_nav_templates", $block);
        self::assertStringContainsString('admin_inbox_templates_page()', $block);
        self::assertStringContainsString("'fa-bolt'", $block);

        $overview = strpos($block, 'crm_nav_inbox_overview');
        $compose = strpos($block, 'crm_inbox_o15_nav_compose');
        $drafts = strpos($block, 'crm_inbox_o15_nav_drafts');
        $templates = strpos($block, 'crm_inbox_o15_nav_templates');
        $diagnostics = strpos($block, 'crm_nav_diagnostics');

        foreach ([$overview, $compose, $drafts, $templates, $diagnostics] as $position) {
            self::assertIsInt($position);
        }
        self::assertLessThan($compose, $overview);
        self::assertLessThan($drafts, $compose);
        self::assertLessThan($templates, $drafts);
        self::assertLessThan($diagnostics, $templates);
    }
}
