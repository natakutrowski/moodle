<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualFormat;

final class commerce_showroom_builder_visual_contract_j13h3_test extends \advanced_testcase {
    public function test_showroom_has_a_dedicated_visual_contract(): void {
        self::assertContains(CommerceProductVisualFormat::SHOWROOM, CommerceProductVisualFormat::all());
        self::assertSame('16:9', CommerceProductVisualFormat::definition(CommerceProductVisualFormat::SHOWROOM)['ratio']);
        self::assertSame(CommerceProductVisualFormat::SHOWROOM, CommerceProductVisualFormat::for_role('showroom'));
        self::assertContains(CommerceProductCoverContext::SHOWROOM, CommerceProductCoverContext::all());
    }

    public function test_builder_uses_one_amd_runtime_and_inert_json_configuration(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $page = file_get_contents($root . '/admin/commerce/showrooms/edit.php');
        $runtime = file_get_contents($root . '/js/showroom_builder.js');
        self::assertStringContainsString("/local/subscriptions/js/showroom_builder.js", $page);
        self::assertStringContainsString("'id' => 'commerce-showroom-builder'", $page);
        self::assertStringContainsString('sesskey', $page);
        self::assertStringContainsString('fetch(config.endpoint', $runtime);


    }

    public function test_product_assets_expose_showroom_master_and_cards_use_sixteen_nine(): void {
        global $CFG;

        $assets = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/products/assets.php');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $resolver = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php');

        self::assertIsString($assets);
        self::assertStringContainsString('ROLE_SHOWROOM', $assets);
        self::assertStringContainsString("'format' => 'showroom'", $assets);

        self::assertIsString($css);
        self::assertStringContainsString('aspect-ratio: 16 / 9;', $css);

        self::assertIsString($resolver);
        self::assertStringContainsString("get_cover_url('showroom')", $resolver);
        self::assertStringContainsString("get_cover_url('product')", $resolver);
        self::assertStringContainsString("get_cover_url('storefront')", $resolver);
    }
}
