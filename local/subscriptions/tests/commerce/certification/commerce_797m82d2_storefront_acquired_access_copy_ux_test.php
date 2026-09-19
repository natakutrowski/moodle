<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromisePresenter;

/** M8.2D2: acquired Storefront states describe current access without repeating purchase promises. */
final class commerce_797m82d2_storefront_acquired_access_copy_ux_test extends advanced_testcase {
    public function test_owned_course_and_digital_are_compact(): void {
        $presenter = new CommerceCustomerAccessPromisePresenter();

        foreach (['owned_course', 'owned_digital'] as $kind) {
            $view = $presenter->present(['kind' => $kind]);

            self::assertNotSame('', trim((string)$view['accesspromiselead']));
            self::assertFalse($view['hasaccesspromisedetails']);
            self::assertSame([], $view['accesspromisedetails']);
        }
    }

    public function test_completed_promotion_join_uses_post_purchase_copy_and_keeps_context(): void {
        $presenter = new CommerceCustomerAccessPromisePresenter();
        $view = $presenter->present([
            'kind' => 'owner_promotion_join_acquired',
            'promotionname' => 'Promotion test',
            'startsat' => 1789800000,
            'firstunlocksat' => 1789803600,
            'progressive' => true,
        ]);

        self::assertSame(
            get_string('commerce_m61_access_lead_owner_promotion_join_acquired', 'local_subscriptions'),
            $view['accesspromiselead']
        );
        self::assertFalse($view['hasaccesspromisedetails']);
        self::assertSame([], $view['accesspromisedetails']);
        self::assertTrue($view['hasaccesspromiseitems']);
        self::assertCount(3, $view['accesspromiseitems']);
    }

    public function test_storefront_maps_already_joined_eligibility_to_acquired_promise(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString(
            'CommercePedagogicalPromotionJoinEligibility::ALREADY_JOINED',
            $source
        );
        self::assertStringContainsString(
            "\$accesspromise['kind'] = 'owner_promotion_join_acquired';",
            $source
        );
    }
}
