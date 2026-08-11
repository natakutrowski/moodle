<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\pricing\CommerceCommercialPriceResolver;

/** Locked Trial price reconstruction without a catalogue row. */
final class commerce_trial_locked_price_resolver_test
        extends \advanced_testcase {

    public function test_locked_trial_total_reconstructs_pre_trial_price(): void {
        global $DB;

        $this->resetAfterTest();

        $item = new CommerceCartItem(
            'MISSING.TRIAL.PRODUCT',
            999999,
            1,
            [
                'operation' => 'trialconversion',
                'trialdiscountpercent' => 20,
            ]
        );
        $calculated = new CommerceCalculatedCartItem(
            $item,
            'Trial product',
            CommerceMoney::from_minor(8000, 'EUR'),
            CommerceMoney::from_minor(8000, 'EUR'),
            1,
            1,
            1,
            'course_access'
        );

        $pricing = (new CommerceCommercialPriceResolver($DB))->resolve(
            $calculated,
            'EUR',
            8000,
            42
        );

        $this->assertSame(10000, $pricing->get_promoted_unit_minor());
        $this->assertSame(2000, $pricing->get_trial_discount_unit_minor());
        $this->assertSame(8000, $pricing->get_final_unit_minor());
        $this->assertSame(0, $pricing->get_owned_credit_unit_minor());
    }
}
