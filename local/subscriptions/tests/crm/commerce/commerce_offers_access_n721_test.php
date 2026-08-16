<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n721_test extends advanced_testcase {
    public function test_shared_communication_cards_are_green_and_red_semantically(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessCampaignRenderer.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            "'class' => 'is-success'",
            $renderer
        );
        self::assertStringContainsString(
            "'class' => \$failed > 0 ? 'is-error' : ''",
            $renderer
        );

        self::assertStringContainsString(
            '.crm-offers-access-campaign-panel'
                . "\n"
                . '.crm-offers-access-campaign-metric.is-success {',
            $styles
        );
        self::assertStringContainsString(
            'background: #effaf4;',
            $styles
        );
        self::assertStringContainsString(
            '.crm-offers-access-campaign-metric.is-error {',
            $styles
        );
        self::assertStringContainsString(
            'background: #fff3f4;',
            $styles
        );
    }

    public function test_n721_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
