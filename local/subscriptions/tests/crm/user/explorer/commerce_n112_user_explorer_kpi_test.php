<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n112_user_explorer_kpi_test extends \advanced_testcase {
    private function read(string $relative): string {
        $path = __DIR__ . '/../../../../' . $relative;
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_user_explorer_exposes_horizontal_kpi_strip(): void {
        $renderer = $this->read('classes/crm/user/explorer/UserExplorerRenderer.php');
        $service = $this->read('classes/crm/user/explorer/UserExplorerService.php');
        $criteria = $this->read('classes/crm/user/explorer/UserExplorerCriteria.php');
        $result = $this->read('classes/crm/user/explorer/UserExplorerResult.php');
        $styles = $this->read('styles.css');

        self::assertStringContainsString('render_kpis($result)', $renderer);
        self::assertStringContainsString('crm-user-explorer-kpi-grid', $renderer);
        self::assertStringContainsString('crm-user-explorer-kpi-card--hot', $styles);
        self::assertStringContainsString('grid-template-columns: repeat(6, minmax(0, 1fr))', $styles);

        self::assertStringContainsString('private function build_kpis', $service);
        self::assertStringContainsString("'hot_leads'", $service);
        self::assertStringContainsString("'at_risk'", $service);
        self::assertStringContainsString("'vip'", $service);
        self::assertStringContainsString("'suspended'", $service);
        self::assertStringContainsString("'no_moodle'", $service);
        self::assertStringContainsString('with_intelligence(string $intelligence)', $criteria);
        self::assertStringContainsString('public readonly array $kpis', $result);
    }

    public function test_kpi_cards_reuse_existing_explorer_filters(): void {
        $renderer = $this->read('classes/crm/user/explorer/UserExplorerRenderer.php');

        self::assertStringContainsString('UserExplorerFilter::HOT_LEAD', $renderer);
        self::assertStringContainsString('UserExplorerFilter::AT_RISK', $renderer);
        self::assertStringContainsString('UserExplorerFilter::VIP', $renderer);
        self::assertStringContainsString("'key' => 'suspended'", $renderer);
        self::assertStringContainsString("'key' => 'no_moodle'", $renderer);
        self::assertStringContainsString("foreach (\$card['params'] as \$key => \$value)", $renderer);
    }
}
