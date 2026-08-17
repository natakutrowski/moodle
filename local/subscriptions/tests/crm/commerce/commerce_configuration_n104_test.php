<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n104_test extends advanced_testcase {
    public function test_configuration_has_internal_navigation_on_overview_and_sections(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/configuration/index.php');
        $section = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $renderer = file_get_contents(
            $root . '/classes/crm/commerce/rendering/CommerceConfigurationNavigationRenderer.php'
        );

        $this->assertStringContainsString(
            'CommerceConfigurationNavigationRenderer::render(CommerceConfigurationNavigationRenderer::OVERVIEW)',
            $index
        );
        $this->assertStringContainsString(
            'CommerceConfigurationNavigationRenderer::render($section)',
            $section
        );
        foreach (['payments', 'localisation', 'checkout', 'communications', 'legal', 'storefront', 'engine'] as $key) {
            $this->assertStringContainsString("'" . $key . "'", $renderer);
        }
    }

    public function test_section_header_is_rendered_before_commerce_navigation(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $header = strpos($source, 'CrmPageHeader::render(');
        $navigation = strpos(
            $source,
            'CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION'
        );

        $this->assertNotFalse($header);
        $this->assertNotFalse($navigation);
        $this->assertLessThan($navigation, $header);
    }

    public function test_dashboard_uses_visual_status_helpers(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/index.php');

        foreach ([
            '$provider(',
            '$environment(',
            '$availabilitybadge(',
            '$language(',
            '$currencyflags(',
            '$runtimebadge(',
        ] as $helper) {
            $this->assertStringContainsString($helper, $source);
        }
    }

    public function test_n104_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $this->assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
