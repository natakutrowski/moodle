<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

final class commerce_796i82_legacy_invoice_compatibility_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_active_legal_entity_editor_does_not_write_legacy_invoice_profiles(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        self::assertStringNotContainsString(
            "set_config(\$legacyprefix . \$legacyfield, \$clean, 'local_subscriptions');",
            $source
        );
        self::assertStringContainsString(
            'Legacy invoice_* profiles are intentionally read-only compatibility',
            $source
        );
    }

    public function test_legal_entity_registry_keeps_read_only_legacy_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/legal/entity/CommerceLegalEntityRegistry.php'
        );

        self::assertStringContainsString(
            "'legacyprefix' => 'invoice_eur_'",
            $source
        );
        self::assertStringContainsString(
            "'legacyprefix' => 'invoice_rub_'",
            $source
        );
        self::assertStringContainsString(
            'I2 compatibility bridge',
            $source
        );
    }

    public function test_legal_editor_does_not_decorate_legacy_profiles_as_payment_providers(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        self::assertStringNotContainsString(
            "str_starts_with(\$key, 'invoice_eur_')",
            $source
        );
        self::assertStringNotContainsString(
            "str_starts_with(\$key, 'invoice_rub_')",
            $source
        );
    }

    public function test_i82_requires_no_schema_change(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertSame(
            1,
            preg_match(
                '/\\$plugin->version\\s*=\\s*(\\d+);/',
                $version,
                $pluginversionmatch
            )
        );
        self::assertGreaterThanOrEqual(
            2026090801,
            (int)$pluginversionmatch[1]
        );
    }
}
