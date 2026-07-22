<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the Command Center internal URL action.
 *
 * @covers \local_subscriptions\commandcenter\actions\OpenUrlAction
 */
final class OpenUrlActionTest
    extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        global $CFG;

        $CFG->wwwroot =
            'https://moodle.example.test';
    }

    public function test_accepts_internal_absolute_url(): void {
        $action = new OpenUrlAction();

        $result = $action->execute(
            [
                'url' =>
                    'https://moodle.example.test'
                    . '/local/subscriptions/admin/dashboard.php',
            ]
        );

        $this->assertTrue(
            $result->jsonSerialize()['success']
        );

        $this->assertSame(
            'https://moodle.example.test'
                . '/local/subscriptions/admin/dashboard.php',
            $result->jsonSerialize()['redirectUrl']
        );
    }

    public function test_accepts_internal_relative_url(): void {
        $action = new OpenUrlAction();

        $result = $action->execute(
            [
                'url' =>
                    '/local/subscriptions/admin/dashboard.php',
            ]
        );

        $data = $result->jsonSerialize();

        $this->assertTrue(
            $data['success']
        );

        $this->assertSame(
            'https://moodle.example.test'
                . '/local/subscriptions/admin/dashboard.php',
            $data['redirectUrl']
        );
    }

    public function test_rejects_external_url(): void {
        $action = new OpenUrlAction();

        $result = $action->execute(
            [
                'url' =>
                    'https://external.example/path',
            ]
        );

        $data = $result->jsonSerialize();

        $this->assertFalse(
            $data['success']
        );

        $this->assertArrayNotHasKey(
            'redirectUrl',
            $data
        );
    }

    public function test_rejects_missing_url(): void {
        $action = new OpenUrlAction();

        $result = $action->execute([]);

        $data = $result->jsonSerialize();

        $this->assertFalse(
            $data['success']
        );
    }
}