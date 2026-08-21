<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1127_user_explorer_nomoodle_sales_table_test extends \advanced_testcase {
    private function file(string $relative): string {
        $path = __DIR__ . '/../../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_no_moodle_is_a_real_account_status_filter(): void {
        $criteria = $this->file(
            'classes/crm/user/explorer/UserExplorerCriteria.php'
        );
        $repository = $this->file(
            'classes/crm/user/explorer/UserExplorerRepository.php'
        );
        $legacy = $this->file(
            'classes/crm/user/explorer/UserExplorerLegacyGuestRepository.php'
        );

        self::assertStringContainsString(
            "public const ACCOUNT_NO_MOODLE = 'no_moodle';",
            $criteria
        );
        self::assertStringContainsString(
            'self::ACCOUNT_NO_MOODLE',
            $criteria
        );
        self::assertStringContainsString(
            'UserExplorerCriteria::ACCOUNT_NO_MOODLE',
            $repository
        );
        self::assertStringContainsString(
            'UserExplorerCriteria::ACCOUNT_NO_MOODLE',
            $legacy
        );
    }

    public function test_no_moodle_kpi_is_clickable_and_filters_explorer(): void {
        $renderer = $this->file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );
        $service = $this->file(
            'classes/crm/user/explorer/UserExplorerService.php'
        );

        self::assertStringContainsString(
            "'accountstatus' => UserExplorerCriteria::ACCOUNT_NO_MOODLE",
            $renderer
        );
        self::assertStringContainsString(
            "'no_moodle' => \$criteria->accountstatus",
            $renderer
        );
        self::assertStringNotContainsString(
            "'tone' => 'nomoodle',\n                'params' => null",
            $renderer
        );
        self::assertStringContainsString(
            'UserExplorerCriteria::ACCOUNT_NO_MOODLE',
            $service
        );
    }

    public function test_user_table_visually_matches_sales_without_vertical_grid(): void {
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-user-explorer-table thead th',
            $styles
        );
        self::assertStringContainsString(
            'background: #fbfcfe !important;',
            $styles
        );
        self::assertStringContainsString(
            'border-right: 0 !important;',
            $styles
        );
        self::assertStringContainsString(
            'border-left: 0 !important;',
            $styles
        );
    }

    public function test_actions_are_left_aligned(): void {
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-user-explorer-table th:last-child',
            $styles
        );
        self::assertStringContainsString(
            'text-align: left !important;',
            $styles
        );
        self::assertStringContainsString(
            'justify-content: flex-start !important;',
            $styles
        );
    }
}
