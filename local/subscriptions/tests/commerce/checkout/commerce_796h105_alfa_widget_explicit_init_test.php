<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h105_alfa_widget_explicit_init_test extends advanced_testcase {

    public function test_executor_explicitly_wakes_official_alfa_widget_after_mount_creation(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        foreach ([
            'const wakeOfficialWidget = log =>',
            "'#alfa-payment-script'",
            "typeof script.init",
            "!== 'function'",
            'script.init();',
            'wakeOfficialWidget(',
            'await waitForOfficialButton(',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
        $build = strpos($js, 'buildWidget(');
        $wake = strpos($js, 'wakeOfficialWidget(', $build);
        $wait = strpos($js, 'await waitForOfficialButton(', $wake);
        $this->assertNotFalse($build);
        $this->assertNotFalse($wake);
        $this->assertNotFalse($wait);
        $this->assertLessThan($wake, $build);
        $this->assertLessThan($wait, $wake);
    }

}
