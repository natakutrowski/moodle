<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;

final class commerce_n119c_similarity_pagination_scoring_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_perfect_checks_can_receive_100_percent(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'same@example.test',
            'phone1' => '+33 6 12 34 56 78',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'same@example.test',
            'phone1' => '+33 6 12 34 56 78',
        ]);

        $match = (
            new CommerceCustomerIdentitySimilarityService($DB)
        )->compare(
            $first,
            $second
        );

        self::assertNotNull($match);
        self::assertSame(100, $match->score);
    }

    public function test_same_name_and_similar_email_is_not_near_certainty(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'nicolas.kutrowski@example.test',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Nicolas',
            'lastname' => 'Kutrowski',
            'email' => 'nicolas.kutrowski2@example.test',
        ]);

        $match = (
            new CommerceCustomerIdentitySimilarityService($DB)
        )->compare(
            $first,
            $second
        );

        self::assertNotNull($match);
        self::assertGreaterThanOrEqual(60, $match->score);
        self::assertLessThan(100, $match->score);

        self::assertSame(
            100,
            $match->checks[
                CommerceCustomerIdentitySimilarityService::CHECK_NAME
            ]['score']
        );
        self::assertLessThan(
            100,
            $match->checks[
                CommerceCustomerIdentitySimilarityService::CHECK_EMAIL
            ]['score']
        );
    }

    public function test_similarity_page_paginates_matches(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/similarities.php'
        );

        foreach ([
            "'page'",
            "'perpage'",
            'array_slice(',
            '$OUTPUT->paging_bar(',
            'crm-identity-similarity-footer',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $page
            );
        }
    }

    public function test_merge_action_has_dedicated_footer_spacing(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/similarities.php'
        );

        self::assertStringContainsString(
            'crm-identity-similarity-merge-action',
            $page
        );
    }
}
