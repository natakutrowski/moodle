<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox;

defined('MOODLE_INTERNAL') || die();

/**
 * O17.1 static/coherence certification guards for the CRM Inbox.
 */
final class commerce_795o17_1_static_coherence_certification_test extends \advanced_testcase {

    public function test_reply_subject_fallback_is_translated_in_supported_languages(): void {
        global $CFG;

        $reply = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/inbox/reply.php'
        );

        $this->assertStringContainsString(
            "'crm_inbox_thread_without_subject'",
            $reply
        );

        foreach (['en', 'fr', 'ru'] as $lang) {
            $languagefile = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );

            $this->assertStringContainsString(
                '$string[\'crm_inbox_thread_without_subject\']',
                $languagefile,
                'Missing thread subject fallback in language ' . $lang
            );
        }
    }

    public function test_diagnostics_covers_o9_template_storage(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox/services/InboxDiagnosticsService.php'
        );

        $this->assertStringContainsString(
            "'local_subscriptions_inbox_template'",
            $service
        );
    }

    public function test_no_invalid_html_writer_section_call_was_introduced_in_inbox_scope(): void {
        global $CFG;

        $roots = [
            $CFG->dirroot . '/local/subscriptions/admin/inbox',
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox',
        ];

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $root,
                    \FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString(
                    'html_writer::section(',
                    $source,
                    'Invalid Moodle html_writer API call in ' . $file->getPathname()
                );
            }
        }
    }
}
