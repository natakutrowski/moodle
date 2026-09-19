<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

final class commerce_796i83_admin_spacing_and_legal_facade_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_historical_seller_panel_has_bottom_spacing_before_metrics(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        self::assertStringContainsString(
            "'mt-3 mb-3'",
            $source
        );
    }

    public function test_region_legal_links_delegate_to_versioned_resolver(): void {
        global $CFG;

        $region = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/support/Region.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/legal/document/CommerceLegalDocumentResolver.php'
        );

        self::assertStringContainsString(
            'CommerceLegalDocumentResolver',
            $region
        );
        self::assertStringContainsString(
            '->resolve(null, current_language())',
            $region
        );
        self::assertStringContainsString(
            "in_array(\$country, ['RU', 'BY'], true)",
            $resolver
        );
        self::assertStringContainsString(
            "\$profile = \$isruby ? 'ru' : 'row';",
            $resolver
        );
        self::assertStringContainsString(
            "'policy_url_' . \$profile",
            $resolver
        );
        self::assertStringContainsString(
            "'terms_url_' . \$profile",
            $resolver
        );
        self::assertStringContainsString(
            "'offer_url_' . \$profile",
            $resolver
        );
    }

    public function test_i83_requires_no_schema_change(): void {
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
