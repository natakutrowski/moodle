<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromisePresenter;

/** M8.2D3: a joined promotion must never be presented as a future purchase. */
final class commerce_797m82d3_storefront_joined_participation_copy_test extends advanced_testcase {
    public function test_acquired_join_copy_contains_no_purchase_or_new_purchase_wording(): void {
        $view = (new CommerceCustomerAccessPromisePresenter())->present([
            'kind' => 'owner_promotion_join_acquired',
            'promotionname' => 'Promotion test',
            'startsat' => 1789800000,
            'firstunlocksat' => 1789803600,
            'progressive' => true,
        ]);

        self::assertFalse($view['hasaccesspromisedetails']);
        self::assertSame([], $view['accesspromisedetails']);
        self::assertStringNotContainsString(
            'achat',
            strtolower((string)$view['accesspromiselead'])
        );
        self::assertStringNotContainsString(
            'nouvel achat',
            strtolower((string)$view['accesspromiselead'])
        );
    }

    public function test_storefront_uses_authoritative_active_participation_as_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString(
            'CommercePedagogicalParticipationRepository::create()->is_active(',
            $source
        );
        self::assertStringContainsString(
            "\$accesspromise['kind'] = 'owner_promotion_join_acquired';",
            $source
        );
        self::assertStringContainsString('$promisepromotionid', $source);
    }
}
