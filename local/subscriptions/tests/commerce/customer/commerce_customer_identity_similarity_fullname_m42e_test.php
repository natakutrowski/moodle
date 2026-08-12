<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;

/**
 * @covers \local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService
 */
final class commerce_customer_identity_similarity_fullname_m42e_test extends advanced_testcase {
    public function test_search_returns_fullname_safe_user_records(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = $this->getDataGenerator()->create_user([
            'firstname' => 'Nataliya',
            'lastname' => 'Kutrowski',
            'email' => 'nataliya.one@example.test',
        ]);
        $second = $this->getDataGenerator()->create_user([
            'firstname' => 'Nataliya',
            'lastname' => 'Kutrowski',
            'email' => 'nataliya.two@example.test',
        ]);

        $result = (new CommerceCustomerIdentitySimilarityService($DB))->search([
            'q' => 'Nataliya',
            'minscore' => 60,
        ]);

        self::assertNotEmpty($result['matches']);

        foreach ($result['matches'] as $match) {
            foreach ([$match->first, $match->second] as $user) {
                self::assertObjectHasProperty('firstnamephonetic', $user);
                self::assertObjectHasProperty('lastnamephonetic', $user);
                self::assertObjectHasProperty('middlename', $user);
                self::assertObjectHasProperty('alternatename', $user);

                // The regression: fullname() must not emit a missing-name-fields debugging call.
                $name = fullname($user);
                self::assertNotSame('', $name);
            }
        }
    }
}
