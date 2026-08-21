<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n119b2_bulk_fullname_runtime_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_bulk_candidate_lookup_requests_all_moodle_name_fields(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/bulk.php'
        );

        self::assertStringContainsString(
            '\\core_user\\fields::get_name_fields()',
            $page
        );
        self::assertStringContainsString(
            'fullname($candidateuser)',
            $page
        );
    }

    public function test_bulk_and_index_keep_shared_human_impact_renderer(): void {
        foreach ([
            'admin/commerce/customer-identities/index.php',
            'admin/commerce/customer-identities/bulk.php',
        ] as $file) {
            $page = $this->file($file);

            self::assertStringContainsString(
                'CommerceCustomerIdentityImpactRenderer::render(',
                $page
            );
        }
    }
}
