<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;

final class commerce_showroom_registry_j13b_test extends \advanced_testcase {
    public function test_third_group_verbs_showroom_is_registered(): void {
        $definition = CommerceShowroomRegistry::require(
            CommerceShowroomRegistry::THIRD_GROUP_VERBS
        );

        self::assertSame('verbes-3e-groupe', $definition->get_slug('fr'));
        self::assertSame('third-group-verbs', $definition->get_slug('en'));
        self::assertSame('glagoly-tretey-gruppy', $definition->get_slug('ru'));
        self::assertSame(
            ['course', 'pdf', 'bundle'],
            array_keys($definition->get_products())
        );
    }

    public function test_registry_resolves_each_localised_slug(): void {
        foreach ([
            'verbes-3e-groupe',
            'third-group-verbs',
            'glagoly-tretey-gruppy',
        ] as $slug) {
            self::assertSame(
                CommerceShowroomRegistry::THIRD_GROUP_VERBS,
                CommerceShowroomRegistry::find_by_slug($slug)?->get_key()
            );
        }
    }
}
