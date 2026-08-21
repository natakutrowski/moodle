<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;

final class commerce_n119d_weighted_similarity_relationships_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_perfect_available_checks_produce_exactly_100_percent(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'nicolas@example.test',
            'phone1' => '+33 6 12 34 56 78',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'nicolas@example.test',
            'phone1' => '+33 6 12 34 56 78',
        ]);

        $match = (
            new CommerceCustomerIdentitySimilarityService($DB)
        )->compare($first, $second);

        self::assertNotNull($match);
        self::assertSame(100, $match->score);
        self::assertSame(
            100,
            $match->checks[
                CommerceCustomerIdentitySimilarityService::CHECK_EMAIL
            ]['score']
        );
        self::assertSame(
            100,
            $match->checks[
                CommerceCustomerIdentitySimilarityService::CHECK_NAME
            ]['score']
        );
        self::assertSame(
            100,
            $match->checks[
                CommerceCustomerIdentitySimilarityService::CHECK_PHONE
            ]['score']
        );
    }

    public function test_weighted_average_does_not_add_percentages(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'nicolas.kutrowski@example.test',
            'phone1' => '+33 6 12 34 56 78',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'nicolas.kutrowski2@example.test',
            'phone1' => '+33 6 12 34 56 78',
        ]);

        $match = (
            new CommerceCustomerIdentitySimilarityService($DB)
        )->compare($first, $second);

        self::assertNotNull($match);

        $email = $match->checks[
            CommerceCustomerIdentitySimilarityService::CHECK_EMAIL
        ]['score'];

        $expected = (int)round(
            (
                $email * 45
                + 100 * 35
                + 100 * 20
            ) / 100
        );

        self::assertSame($expected, $match->score);
        self::assertLessThan(100, $match->score);
    }

    public function test_similarity_ui_exposes_score_and_weight_per_check(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/similarities.php'
        );

        foreach ([
            'crm-identity-similarity-check-score',
            'crm-identity-similarity-check-weight',
            'check_weight(',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $page
            );
        }
    }

    public function test_relationships_screen_has_clear_two_entry_inspector(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/relationships.php'
        );

        foreach ([
            'crm-identity-relationship-inspector',
            'crm_identity_relationships_moodle_account',
            'crm_identity_relationships_external_identity',
            'crm_identity_relationships_empty_help',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $page
            );
        }
    }
}
