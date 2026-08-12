<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\user\explorer\UserExplorerService;
use local_subscriptions\crm\user\explorer\UserExplorerSort;

/**
 * Regression for namespaced core_text resolution in User Explorer sorting.
 */
final class crm_user_explorer_core_text_m43a_test extends advanced_testcase {
    public function test_name_sort_uses_core_text_without_namespace_error(): void {
        $service = new UserExplorerService();

        $method = new \ReflectionMethod(
            UserExplorerService::class,
            'compare_records'
        );
        $method->setAccessible(true);

        $alice = (object)[
            'id' => 10,
            'firstname' => 'Alice',
            'lastname' => 'Martin',
            'email' => 'alice@example.test',
        ];
        $zoe = (object)[
            'id' => 11,
            'firstname' => 'Zoé',
            'lastname' => 'Bernard',
            'email' => 'zoe@example.test',
        ];

        $result = $method->invoke(
            $service,
            $alice,
            $zoe,
            UserExplorerSort::NAME_ASC
        );

        self::assertIsInt($result);
    }
}
