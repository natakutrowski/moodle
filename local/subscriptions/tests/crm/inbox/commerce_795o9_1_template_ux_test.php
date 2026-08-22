<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o9_1_template_ux_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_signature_subject_field_is_dynamic(): void {
        $page = $this->file(
            'admin/inbox/templates.php'
        );

        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'data-inbox-template-type',
            $page
        );

        self::assertStringContainsString(
            'data-inbox-template-subject-field',
            $page
        );

        self::assertStringContainsString(
            'syncTemplateTypeFields',
            $js
        );

        self::assertStringContainsString(
            "select.value === 'signature'",
            $js
        );
    }

    public function test_template_form_has_contextual_help(): void {
        $page = $this->file(
            'admin/inbox/templates.php'
        );

        foreach (
            [
                'crm_inbox_template_type_help_o91',
                'crm_inbox_template_name_help_o91',
                'crm_inbox_template_account_help_o91',
                'crm_inbox_template_subject_help_o91',
                'crm_inbox_template_content_help_o91',
                'crm_inbox_template_sortorder_help_o91',
            ]
            as $key
        ) {
            self::assertStringContainsString(
                $key,
                $page
            );
        }
    }
}
