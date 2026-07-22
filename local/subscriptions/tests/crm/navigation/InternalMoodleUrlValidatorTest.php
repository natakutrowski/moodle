<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for internal Moodle URL validation.
 *
 * @covers \local_subscriptions\crm\navigation\InternalMoodleUrlValidator
 */
final class InternalMoodleUrlValidatorTest
    extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        global $CFG;

        $CFG->wwwroot =
            'https://moodle.example.test';
    }

    public function test_accepts_root_relative_url(): void {
        $url = InternalMoodleUrlValidator::normalise(
            '/local/subscriptions/admin/dashboard.php'
        );

        $this->assertNotNull(
            $url
        );

        $this->assertSame(
            'https://moodle.example.test'
                . '/local/subscriptions/admin/dashboard.php',
            $url->out(false)
        );
    }

    public function test_accepts_absolute_url_from_wwwroot(): void {
        $url = InternalMoodleUrlValidator::normalise(
            'https://moodle.example.test'
                . '/local/subscriptions/admin/users/index.php?id=12'
        );

        $this->assertNotNull(
            $url
        );

        $this->assertSame(
            'https://moodle.example.test'
                . '/local/subscriptions/admin/users/index.php?id=12',
            $url->out(false)
        );
    }

    public function test_accepts_wwwroot_itself(): void {
        $url = InternalMoodleUrlValidator::normalise(
            'https://moodle.example.test'
        );

        $this->assertNotNull(
            $url
        );

        $this->assertSame(
            'https://moodle.example.test',
            $url->out(false)
        );
    }

    public function test_accepts_query_on_wwwroot(): void {
        $url = InternalMoodleUrlValidator::normalise(
            'https://moodle.example.test?redirect=1'
        );

        $this->assertNotNull(
            $url
        );
    }

    public function test_accepts_fragment_on_wwwroot(): void {
        $url = InternalMoodleUrlValidator::normalise(
            'https://moodle.example.test#maincontent'
        );

        $this->assertNotNull(
            $url
        );
    }

    public function test_rejects_empty_url(): void {
        $this->assertNull(
            InternalMoodleUrlValidator::normalise('')
        );

        $this->assertNull(
            InternalMoodleUrlValidator::normalise('   ')
        );
    }

    public function test_rejects_external_absolute_url(): void {
        $this->assertNull(
            InternalMoodleUrlValidator::normalise(
                'https://example.com/path'
            )
        );
    }

    public function test_rejects_similar_external_hostname(): void {
        $this->assertNull(
            InternalMoodleUrlValidator::normalise(
                'https://moodle.example.test.evil.example/path'
            )
        );
    }

    public function test_rejects_protocol_relative_url(): void {
        $this->assertNull(
            InternalMoodleUrlValidator::normalise(
                '//example.com/path'
            )
        );
    }

    public function test_rejects_javascript_url(): void {
        $this->assertNull(
            InternalMoodleUrlValidator::normalise(
                'javascript:alert(1)'
            )
        );
    }

    public function test_rejects_data_url(): void {
        $this->assertNull(
            InternalMoodleUrlValidator::normalise(
                'data:text/html,test'
            )
        );
    }

    public function test_supports_moodle_installed_in_subdirectory(): void {
        global $CFG;

        $CFG->wwwroot =
            'https://example.test/moodle';

        $url = InternalMoodleUrlValidator::normalise(
            'https://example.test/moodle'
                . '/local/subscriptions/admin/dashboard.php'
        );

        $this->assertNotNull(
            $url
        );

        $this->assertSame(
            'https://example.test/moodle'
                . '/local/subscriptions/admin/dashboard.php',
            $url->out(false)
        );
    }

    public function test_rejects_sibling_path_of_subdirectory_install(): void {
        global $CFG;

        $CFG->wwwroot =
            'https://example.test/moodle';

        $this->assertNull(
            InternalMoodleUrlValidator::normalise(
                'https://example.test/moodle-evil/path'
            )
        );
    }

    public function test_is_internal_uses_same_validation(): void {
        $this->assertTrue(
            InternalMoodleUrlValidator::is_internal(
                '/local/subscriptions/admin/dashboard.php'
            )
        );

        $this->assertFalse(
            InternalMoodleUrlValidator::is_internal(
                'https://example.com'
            )
        );
    }
}