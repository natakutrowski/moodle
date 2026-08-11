<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p6_test extends \advanced_testcase {
    public function test_dynamic_legal_footer_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $ajax = file_get_contents($root . 'ajax/showroom_prices.php');
        $js = file_get_contents($root . 'amd/src/showroom.js');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString("'legalshowname'", $registry);
        self::assertStringContainsString("'legalshowfooter'", $registry);
        self::assertStringContainsString("'legalshowoffer'", $registry);
        self::assertStringContainsString("'finallegalshow' . \$legalfield", $presenter);

        self::assertStringContainsString('data-showroom-final-legal', $template);
        self::assertStringContainsString('data-showroom-legal-field="name"', $template);
        self::assertStringContainsString('data-showroom-legal-field="footer"', $template);

        self::assertStringContainsString("'legalprofile' => \$legalprofile", $ajax);
        self::assertStringContainsString('updateLegalProfile(payload.legalprofile || {});', $js);
        self::assertStringContainsString('white-space: pre-line;', $css);
        self::assertStringContainsString('--showroom-final-mobile-bg-offset-x: 150px;', $css);

        // P8 deliberately replaced the failed mobile fixed-sticky clearance strategy.
        self::assertStringContainsString('data-showroom-final-inline-mobile-cta', $template);
        self::assertStringNotContainsString('observeMobileFinalCtaSticky', $js);
    }
}
