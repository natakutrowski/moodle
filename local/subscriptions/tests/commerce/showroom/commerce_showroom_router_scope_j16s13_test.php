<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_router_scope_j16s13_test extends \advanced_testcase {
    public function test_showroom_renderer_imports_required_moodle_globals(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/public_router.php'
        );

        self::assertStringContainsString(
            '$renderShowroom = static function (): never {',
            $source
        );

        self::assertStringContainsString(
            'global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION;',
            $source
        );

        $globalpos = strpos(
            $source,
            'global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION;'
        );
        $requirepos = strpos(
            $source,
            "require(__DIR__ . '/showroom.php');"
        );

        self::assertNotFalse($globalpos);
        self::assertNotFalse($requirepos);
        self::assertLessThan($requirepos, $globalpos);
    }
}
