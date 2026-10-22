<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomPresentationProfileRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomThirdGroupVerbsPresentationProfile;

final class commerce_showroom_presentation_profile_798a2_test extends \advanced_testcase {
    public function test_current_verbs_showroom_resolves_an_explicit_presentation_profile(): void {
        $definition = CommerceShowroomRegistry::require(
            CommerceShowroomRegistry::THIRD_GROUP_VERBS
        );

        $profile = CommerceShowroomPresentationProfileRegistry::for_definition($definition);
        self::assertInstanceOf(CommerceShowroomThirdGroupVerbsPresentationProfile::class, $profile);
    }

    public function test_generic_presenter_no_longer_contains_verbs_offer_role_merchandising(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/showroom/'
                . 'CommerceShowroomPresenter.php'
        );
        $statepresenter = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/showroom/'
                . 'CommerceShowroomOfferStatePresenter.php'
        );

        self::assertIsString($presenter);
        self::assertIsString($statepresenter);
        foreach (['course', 'pdf', 'bundle'] as $legacyrole) {
            self::assertStringNotContainsString("'{$legacyrole}'", $presenter);
            self::assertStringNotContainsString("'{$legacyrole}'", $statepresenter);
        }
        self::assertStringContainsString(
            'CommerceShowroomPresentationProfileRegistry::for_definition',
            $presenter
        );
        self::assertStringContainsString(
            'CommerceShowroomOfferStatePresenter',
            $presenter
        );
    }

    public function test_verbs_profile_preserves_historical_merchandising_rules(): void {
        $profile = new CommerceShowroomThirdGroupVerbsPresentationProfile();
        $offers = $profile->decorate_offers([
            [
                'role' => 'pdf',
                'owned' => true,
                'bundleblocked' => false,
            ],
            [
                'role' => 'course',
                'owned' => false,
                'bundleblocked' => false,
            ],
            [
                'role' => 'bundle',
                'owned' => false,
                'bundleblocked' => false,
            ],
            [
                'role' => 'support',
                'owned' => false,
                'bundleblocked' => false,
            ],
        ]);

        $byrole = [];
        foreach ($offers as $offer) {
            $byrole[(string)$offer['role']] = $offer;
        }

        self::assertFalse($byrole['pdf']['ispreferred']);
        self::assertTrue($byrole['course']['ispreferred']);
        self::assertTrue($byrole['bundle']['isfeatured']);
        self::assertFalse($byrole['support']['isfeatured']);
        self::assertFalse($byrole['support']['hasfeatures']);
        self::assertCount(4, $byrole['course']['features']);
    }

    public function test_verbs_fallback_data_remains_in_the_verbs_profile(): void {
        $profile = new CommerceShowroomThirdGroupVerbsPresentationProfile();
        $data = $profile->fallback_data([
            [
                'role' => 'bundle',
                'owned' => false,
                'available' => true,
                'priceformatted' => '42 €',
            ],
        ]);

        self::assertSame('180', $data['herostat1value']);
        self::assertSame('30', $data['herostat2value']);
        self::assertSame('4000+', $data['herostat3value']);
        self::assertSame('42 €', $data['desktopstickyprice']);
        self::assertTrue($data['hasdesktopstickyprice']);
        self::assertSame('prospect', $data['herostatus']);
    }
}
