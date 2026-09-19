<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5711_guest_email_hint_esmodule_test extends \advanced_testcase {
    public function test_legacy_email_hint_uses_moodle_es_module_source_format(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_email_hint.js'
        );

        self::assertStringContainsString(
            'export const init = () => {',
            $amd
        );
        self::assertStringNotContainsString(
            'define(',
            $amd
        );
        self::assertStringNotContainsString(
            '.exists',
            $amd
        );
    }
}
