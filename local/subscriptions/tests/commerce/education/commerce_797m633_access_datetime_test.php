<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromisePresenter;

final class commerce_797m633_access_datetime_test extends advanced_testcase {
    public function test_promotion_start_and_first_unlock_include_time(): void {
        $this->resetAfterTest();

        $start = time() + (2 * HOURSECS);
        $unlock = $start + HOURSECS;

        $context = (new CommerceCustomerAccessPromisePresenter())->present([
            'kind' => 'promotion_progressive',
            'promotionname' => 'M6.3 datetime',
            'startsat' => $start,
            'firstunlocksat' => $unlock,
            'progressive' => true,
        ]);

        $labels = array_column($context['accesspromiseitems'], 'label');
        $expectedstart = get_string(
            'commerce_m61_access_starts',
            'local_subscriptions',
            userdate($start, get_string('strftimedatetime', 'langconfig'))
        );
        $expectedunlock = get_string(
            'commerce_m61_access_first_unlock',
            'local_subscriptions',
            userdate($unlock, get_string('strftimedatetime', 'langconfig'))
        );

        self::assertContains($expectedstart, $labels);
        self::assertContains($expectedunlock, $labels);
    }
}
