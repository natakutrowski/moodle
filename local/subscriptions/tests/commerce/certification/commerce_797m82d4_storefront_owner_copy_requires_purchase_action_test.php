<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromisePresenter;

/** M8.2D4: purchase-oriented owner copy may only appear with a real join action. */
final class commerce_797m82d4_storefront_owner_copy_requires_purchase_action_test extends advanced_testcase {
    public function test_non_purchasable_owned_course_copy_contains_no_purchase_language(): void {
        $view = (new CommerceCustomerAccessPromisePresenter())->present([
            'kind' => 'owned_course',
        ]);

        self::assertFalse($view['hasaccesspromisedetails']);
        self::assertSame([], $view['accesspromisedetails']);
        self::assertStringNotContainsString('achat', strtolower((string)$view['accesspromiselead']));
        self::assertFalse($view['hasaccesspromiseitems']);
    }

    public function test_storefront_guards_owner_purchase_copy_with_real_join_purchasability(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString(
            <<<'PHP'
($accesspromise['kind'] ?? '') === 'owner_promotion_join'
PHP,
            $source
        );
        self::assertStringContainsString('&& !$promotionjoinpurchasable', $source);
        self::assertStringContainsString("'kind' => 'owned_course'", $source);
        self::assertStringContainsString("'promotionid' => null", $source);
        self::assertStringContainsString("'promotionname' => null", $source);
    }

    public function test_true_joined_state_still_has_priority_over_non_purchasable_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'
        );
        self::assertIsString($source);

        $joined = strpos($source, 'if ($alreadyjoined) {');
        $fallback = strpos($source, '&& !$promotionjoinpurchasable');
        self::assertNotFalse($joined);
        self::assertNotFalse($fallback);
        self::assertLessThan($fallback, $joined);
    }
}
