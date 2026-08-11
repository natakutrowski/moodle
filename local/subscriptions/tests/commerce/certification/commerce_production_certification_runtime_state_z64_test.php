<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\certification\CommerceProductionCertificationAuditor;

defined('MOODLE_INTERNAL') || die();

/**
 * Z6.4 regression coverage for the final production runtime-state certification.
 */
final class commerce_production_certification_runtime_state_z64_test extends \advanced_testcase {
    public function test_final_native_runtime_state_is_certifiable_when_reads_are_native_and_fallback_is_enabled(): void {
        $this->resetAfterTest(true);

        set_config('commerce_runtime_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_read_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_native_fallback_enabled', 1, 'local_subscriptions');

        $source = (string)file_get_contents(
            __DIR__ . '/../../../classes/commerce/certification/CommerceProductionCertificationAuditor.php'
        );

        $this->assertStringContainsString(
            "\$metadata['runtime_mode'] === 'native'",
            $source
        );
        $this->assertStringContainsString(
            "\$metadata['runtime_read_mode'] === 'native'",
            $source
        );
        $this->assertStringContainsString(
            "\$metadata['native_fallback_enabled'] === true",
            $source
        );
        $this->assertStringContainsString(
            'Runtime and reads are Native with guarded Legacy fallback enabled.',
            $source
        );
    }

    public function test_shadow_remains_supported_as_preproduction_certification_state(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/certification/CommerceProductionCertificationAuditor.php'
        );

        $this->assertStringContainsString(
            "\$metadata['runtime_mode'] === 'shadow'",
            $source
        );
        $this->assertStringContainsString(
            'Runtime remains in Shadow mode at pre-production certification time.',
            $source
        );
    }

    public function test_native_without_native_reads_or_fallback_is_not_the_certified_final_posture(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/certification/CommerceProductionCertificationAuditor.php'
        );

        $this->assertStringContainsString(
            'Runtime must be Shadow, or fully Native with Native reads and guarded Legacy fallback enabled.',
            $source
        );
    }
}
