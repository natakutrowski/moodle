<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n129c1_customer_success_action_context_fix_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_plan_action_initialises_page_context_before_lifecycle_operations(): void {
        $page = $this->file(
            'admin/assistant/plan_action.php'
        );

        self::assertStringContainsString(
            '$context = AdminSecurity::require(',
            $page
        );
        self::assertStringContainsString(
            '$PAGE->set_context(',
            $page
        );
        self::assertStringContainsString(
            '$PAGE->set_url(',
            $page
        );

        self::assertLessThan(
            strpos(
                $page,
                '$lifecycle ='
            ),
            strpos(
                $page,
                '$PAGE->set_context('
            )
        );
    }
}
