<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;

/**
 * @covers \local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService
 */
final class commerce_customer_identity_similarity_m42b_test extends advanced_testcase {
    public function test_exact_name_and_close_email_are_suggested_with_explanations(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Nataliya',
            'lastname' => 'Kutrowski',
            'email' => 'nataliya.kutrowski@example.com',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Nataliya',
            'lastname' => 'Kutrowski',
            'email' => 'nataliya.kutrowski+shop@example.net',
        ]);

        $service = new CommerceCustomerIdentitySimilarityService($DB);
        $match = $service->compare($first, $second);

        self::assertNotNull($match);
        self::assertGreaterThanOrEqual(60, $match->score);
        self::assertContains(
            CommerceCustomerIdentitySimilarityService::REASON_NAME_EXACT,
            $match->reasons
        );
    }

    public function test_reversed_first_and_last_name_are_detected(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Natalia',
            'lastname' => 'Martin',
            'email' => 'natalia.one@example.com',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Martin',
            'lastname' => 'Natalia',
            'email' => 'other@example.net',
        ]);

        $match = (new CommerceCustomerIdentitySimilarityService($DB))->compare(
            $first,
            $second
        );

        self::assertNotNull($match);
        self::assertSame(60, $match->score);
        self::assertContains(
            CommerceCustomerIdentitySimilarityService::REASON_NAME_REVERSED,
            $match->reasons
        );
    }

    public function test_same_phone_is_a_strong_signal(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice',
            'lastname' => 'One',
            'email' => 'alice.one@example.com',
            'phone1' => '+33 6 12 34 56 78',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Alicia',
            'lastname' => 'Other',
            'email' => 'different@example.net',
            'phone2' => '06 12 34 56 78',
        ]);

        $match = (new CommerceCustomerIdentitySimilarityService($DB))->compare(
            $first,
            $second
        );

        self::assertNotNull($match);
        self::assertGreaterThanOrEqual(75, $match->score);
        self::assertContains(
            CommerceCustomerIdentitySimilarityService::REASON_PHONE_EXACT,
            $match->reasons
        );
    }

    public function test_unrelated_accounts_are_not_suggested(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice',
            'lastname' => 'Martin',
            'email' => 'alice@example.com',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Boris',
            'lastname' => 'Petrov',
            'email' => 'boris@example.net',
        ]);

        self::assertNull(
            (new CommerceCustomerIdentitySimilarityService($DB))->compare(
                $first,
                $second
            )
        );
    }

    public function test_search_returns_suggestions_but_never_changes_accounts(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Marie',
            'lastname' => 'Dupont',
            'email' => 'marie.dupont@example.com',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Marie',
            'lastname' => 'Dupont',
            'email' => 'marie.dupont@example.net',
        ]);

        $beforefirst = $DB->get_record('user', ['id' => $first->id], '*', MUST_EXIST);
        $beforesecond = $DB->get_record('user', ['id' => $second->id], '*', MUST_EXIST);

        $result = (new CommerceCustomerIdentitySimilarityService($DB))->search([
            'q' => 'Marie',
            'minscore' => 60,
        ]);

        self::assertNotEmpty($result['matches']);

        $afterfirst = $DB->get_record('user', ['id' => $first->id], '*', MUST_EXIST);
        $aftersecond = $DB->get_record('user', ['id' => $second->id], '*', MUST_EXIST);

        self::assertSame($beforefirst->suspended, $afterfirst->suspended);
        self::assertSame($beforefirst->email, $afterfirst->email);
        self::assertSame($beforesecond->suspended, $aftersecond->suspended);
        self::assertSame($beforesecond->email, $aftersecond->email);
    }
}
