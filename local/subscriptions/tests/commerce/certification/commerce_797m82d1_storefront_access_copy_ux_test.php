<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromisePresenter;

/** M8.2D1: keep simple Storefront access promises concise while preserving rich pedagogical detail. */
final class commerce_797m82d1_storefront_access_copy_ux_test extends advanced_testcase {
    public function test_simple_course_and_digital_promises_do_not_repeat_the_lead(): void {
        $presenter = new CommerceCustomerAccessPromisePresenter();

        foreach (['immediate_course', 'immediate_digital'] as $kind) {
            $view = $presenter->present(['kind' => $kind]);

            self::assertNotSame('', trim((string)$view['accesspromiselead']));
            self::assertFalse($view['hasaccesspromisedetails']);
            self::assertSame([], $view['accesspromisedetails']);
        }
    }

    public function test_complex_promises_keep_complementary_details(): void {
        $presenter = new CommerceCustomerAccessPromisePresenter();

        $progressive = $presenter->present([
            'kind' => 'promotion_progressive',
            'progressive' => true,
        ]);
        $ownerjoin = $presenter->present(['kind' => 'owner_promotion_join']);
        $bundle = $presenter->present(['kind' => 'bundle']);

        self::assertTrue($progressive['hasaccesspromisedetails']);
        self::assertCount(2, $progressive['accesspromisedetails']);
        self::assertTrue($ownerjoin['hasaccesspromisedetails']);
        self::assertCount(3, $ownerjoin['accesspromisedetails']);
        self::assertTrue($bundle['hasaccesspromisedetails']);
        self::assertCount(1, $bundle['accesspromisedetails']);
    }
}
