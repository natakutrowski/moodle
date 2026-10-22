<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * J16A.5 contract: the featured offer gains height and hover lift without scale enlargement.
 *
 * The merchandising role is deliberately irrelevant here: the generic Showroom engine must
 * apply the featured geometry to whichever offer is selected as featured by the presentation.
 *
 * @coversNothing
 */
final class commerce_showroom_bundle_height_j16a5_test extends \advanced_testcase {
    public function test_featured_offer_uses_height_and_hover_lift_without_scale_enlargement(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);

        self::assertMatchesRegularExpression(
            '/\.commerce-showroom-offer\.is-featured\s*\{[^}]*height:\s*calc\(100% \+ 1\.5rem\)/s',
            $css
        );
        self::assertMatchesRegularExpression(
            '/\.commerce-showroom-offer\.is-featured:hover[^{]*\{[^}]*translateY\(-16px\)/s',
            $css
        );
        self::assertMatchesRegularExpression(
            '/J16A\.5[\s\S]*?\.commerce-showroom-offer\.is-featured\s*\{[^}]*transform:\s*none\s*!important;/s',
            $css
        );
    }
}
