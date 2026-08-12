<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;

/** @covers \local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService */
final class commerce_795m71_m72_similarity_engine_test extends advanced_testcase {
    public function test_email_domain_typo_is_explained_and_weighted(): void {
        global $DB;
        $this->resetAfterTest(true);
        $a = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Strelnik', 'email' => 'nastyastrelnik@gmal.com']);
        $b = $this->getDataGenerator()->create_user(['firstname' => 'Anastasia', 'lastname' => 'Strelnik', 'email' => 'nastyastrelnik@gmail.com']);
        $match = (new CommerceCustomerIdentitySimilarityService($DB))->compare($a, $b);
        self::assertNotNull($match);
        self::assertContains(CommerceCustomerIdentitySimilarityService::REASON_EMAIL_DOMAIN_CLOSE, $match->reasons);
        self::assertArrayHasKey(CommerceCustomerIdentitySimilarityService::REASON_EMAIL_DOMAIN_CLOSE, $match->signalweights);
        self::assertGreaterThanOrEqual(50, $match->score);
    }

    public function test_alternate_name_can_strengthen_a_candidate(): void {
        global $DB;
        $this->resetAfterTest(true);
        $a = $this->getDataGenerator()->create_user(['firstname' => 'Ekaterina', 'lastname' => 'Ivanova', 'alternatename' => 'Katya', 'email' => 'katya.one@example.test']);
        $b = $this->getDataGenerator()->create_user(['firstname' => 'Katya', 'lastname' => 'Ivanova', 'email' => 'other@example.test']);
        $match = (new CommerceCustomerIdentitySimilarityService($DB))->compare($a, $b);
        self::assertNotNull($match);
        self::assertContains(CommerceCustomerIdentitySimilarityService::REASON_ALTERNATE_NAME, $match->reasons);
    }

    public function test_search_recall_accepts_close_names_without_exact_fullname(): void {
        global $DB;
        $this->resetAfterTest(true);
        $a = $this->getDataGenerator()->create_user(['firstname' => 'Natalia', 'lastname' => 'Kowalska', 'email' => 'first@example.test']);
        $b = $this->getDataGenerator()->create_user(['firstname' => 'Nataliya', 'lastname' => 'Kowalska', 'email' => 'second@example.test']);
        $result = (new CommerceCustomerIdentitySimilarityService($DB))->search(['q' => 'Kowalska', 'minscore' => 40]);
        $keys = array_map(static fn($m): string => $m->key(), $result['matches']);
        $ids = [(int)$a->id, (int)$b->id]; sort($ids);
        self::assertContains($ids[0] . ':' . $ids[1], $keys);
    }

    public function test_unrelated_email_domains_are_not_a_signal(): void {
        global $DB;
        $this->resetAfterTest(true);
        $a = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Martin', 'email' => 'alice@outlook.com']);
        $b = $this->getDataGenerator()->create_user(['firstname' => 'Boris', 'lastname' => 'Petrov', 'email' => 'boris@gmail.com']);
        $match = (new CommerceCustomerIdentitySimilarityService($DB))->compare($a, $b);
        self::assertNull($match);
    }
}
