<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\pricing\CommerceCommercialPriceResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomOfferConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductConfig;

/** 7.98B.4.1 — fixes real Showroom checkout + three-option donation merchandising. */
final class commerce_798b41_donation_showroom_checkout_fix_test extends \advanced_testcase {
    public function test_selected_donation_amount_is_a_valid_commercial_price(): void {
        global $DB;
        $this->resetAfterTest(true);

        $item = new CommerceCartItem(
            'DONATION.B41',
            42,
            1,
            ['donation_units' => 25]
        );
        $calculated = new CommerceCalculatedCartItem(
            $item,
            'Donation B4.1',
            CommerceMoney::from_minor(2500, 'EUR'),
            CommerceMoney::from_minor(2500, 'EUR'),
            1,
            1,
            1,
            CommerceProductType::DONATION
        );

        $pricing = (new CommerceCommercialPriceResolver($DB))->resolve(
            $calculated,
            'EUR',
            2500,
            42
        );

        self::assertSame(2500, $pricing->get_initial_unit_minor());
        self::assertSame(2500, $pricing->get_promoted_unit_minor());
        self::assertSame(2500, $pricing->get_final_unit_minor());
        self::assertSame(0, $pricing->get_promotion_unit_minor());
        self::assertSame(0, $pricing->get_trial_discount_unit_minor());
        self::assertSame(0, $pricing->get_owned_credit_unit_minor());
        self::assertSame(0, $pricing->get_adjustment_discount_unit_minor());
        self::assertSame(0, $pricing->get_total_reduction_minor());
    }

    public function test_offer_configuration_supports_course_only_required_companion_and_donation_only(): void {
        $this->resetAfterTest(true);

        $products = CommerceShowroomProductConfig::from_json(
            CommerceShowroomProductConfig::to_json([
                'course' => 'COURSE.ALF',
                'course_support' => 'COURSE.ALF',
                'support' => 'DONATION.ALF',
            ])
        );
        self::assertSame('COURSE.ALF', $products['course']);
        self::assertSame('COURSE.ALF', $products['course_support']);
        self::assertSame('DONATION.ALF', $products['support']);

        $json = CommerceShowroomOfferConfig::merge_into_settings_json('{}', [
            'course' => [
                'detailsenabled' => true,
                'donationmode' => CommerceShowroomOfferConfig::DONATION_MODE_NONE,
            ],
            'course_support' => [
                'detailsenabled' => true,
                'donationmode' => CommerceShowroomOfferConfig::DONATION_MODE_REQUIRED,
            ],
            'support' => [
                'detailsenabled' => true,
                'donationmode' => CommerceShowroomOfferConfig::DONATION_MODE_NONE,
            ],
        ]);
        $config = CommerceShowroomOfferConfig::from_settings_json($json);

        self::assertSame('none', $config['course']['donationmode']);
        self::assertSame('required', $config['course_support']['donationmode']);
        self::assertSame('none', $config['support']['donationmode']);
    }

    public function test_showroom_js_synchronises_real_form_field_and_template_exposes_required_mode(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $js = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        $template = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        $admin = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php');
        $css = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString("offer.querySelectorAll('[data-donation-units-hidden]')", $js);
        self::assertStringContainsString("root.dataset.donationCompanionMode === 'required'", $js);
        self::assertStringContainsString('data-showroom-base-amount-minor', $template);
        self::assertStringContainsString('donationcompanionrequired', $template);
        self::assertStringContainsString('commerce_donation_companion_included', $template);
        self::assertStringContainsString('offerdonationmode_', $admin);
        self::assertStringContainsString('DONATION_MODE_REQUIRED', $admin);
        self::assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
    }
}
