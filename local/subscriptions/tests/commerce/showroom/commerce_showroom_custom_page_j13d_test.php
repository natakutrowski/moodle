<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;

final class commerce_showroom_custom_page_j13d_test extends \advanced_testcase {
    public function test_showroom_uses_current_product_skus(): void {
        $definition = \local_subscriptions\commerce\showroom\CommerceShowroomRegistry::require(
            \local_subscriptions\commerce\showroom\CommerceShowroomRegistry::THIRD_GROUP_VERBS
        );
        $products = $definition->get_products();
        self::assertSame('COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE', $products['course']);
        self::assertSame('DIGITAL.VERBES-3E-GROUPE', $products['pdf']);
        self::assertSame('BUNDLE.THIRD_GROUP_VERBS_BUNDLE', $products['bundle']);

    }

    public function test_custom_page_contains_marketing_sections_and_full_width_video(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/showroom/'
                . 'third_group_verbs.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertIsString($template);
        self::assertIsString($css);
        self::assertStringContainsString('id="showroom-video"', $template);
        self::assertStringContainsString('id="showroom-offers"', $template);
        self::assertStringContainsString('data-showroom-faq', $template);
        self::assertStringContainsString('data-showroom-sticky-cta', $template);
        self::assertStringContainsString('.commerce-showroom-video__frame', $css);
        self::assertStringContainsString('aspect-ratio: 16 / 7', $css);
    }

    public function test_bundle_is_presented_as_featured_offer(): void {
        global $CFG;
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomPresenter.php');
        $configuration = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        self::assertStringContainsString("\$offer['isfeatured'] = \$role === 'bundle'", $presenter);
        self::assertStringContainsString("\$featuredrole = \$this->text(\$config, 'featuredrole', 'bundle')", $configuration);


    }
}
